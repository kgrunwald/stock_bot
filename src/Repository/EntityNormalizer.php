<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\Plan;
use App\Entity\Security;
use App\Entity\User;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class EntityNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    const FORMAT = 'dynamodb';

    const mapping = [
        Security::class => [
            DynamoRepository::GSI1 => 'type'
        ],
        Goal::class => [
            DynamoRepository::GSI1 => 'userId'
        ],
        Plan::class => [
            DynamoRepository::GSI1 => 'id'
        ],
        Holding::class => [
            DynamoRepository::GSI1 => 'symbol'
        ]
    ];

    const contexts = [
        User::class => [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password', 'salt', 'goals', 'newRecord', 'username']],
    ];

    const relations = [
        Goal::class => ['holdings', 'plan']
    ];

    const staticFields = [
        Plan::class => [DynamoRepository::GSI2 => 'plan'],
        Security::class => [DynamoRepository::GSI2 => 'security']
    ];

    private ArrayDenormalizer $arrayDenormalizer;
    private GetSetMethodNormalizer $g;

    public function __construct(LoggerInterface $logger, ObjectNormalizer $normalizer)
    {

        $this->logger = $logger;
        $this->normalizer = $normalizer;
        // $this->arrayDenormalizer = new ArrayDenormalizer();
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context = array_merge($context, self::contexts[get_class($object)] ?? []);
        if (!isset($context['PK'])) {
            $context['PK'] = $object->getId();
        }

        if (!isset($context['SK'])) {
            $context['SK'] = '';
        }

        return $this->normalizeEntity($object, $context);
    }

    public function normalizeEntity($object, $context)
    {
        $entities = [];
        if (is_array($object)) {
            foreach ($object as $value) {
                $entities = array_merge($entities, $this->normalizeEntity($value, $context));
            }
            return $entities;
        }

        $relations = self::relations[get_class($object)] ?? [];
        if (!isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES])) {
            $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = [];
        }
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = array_merge($context[AbstractNormalizer::IGNORED_ATTRIBUTES], $relations);
        $context['SK'] = $context['SK'] . $object->getId();

        if ($object instanceof Entity) {
            $object->setUpdatedAt(new DateTime());
            if (!$object->getCreatedAt()) {
                $object->setCreatedAt($object->getUpdatedAt());
            }
        }

        $normalized = $this->normalizer->normalize($object, null, $context);

        // Map fields to GSI attributes
        $classEntry = self::mapping[get_class($object)] ?? [];
        foreach ($classEntry as $indexKey => $entityKey) {
            $normalized[$indexKey] = $normalized[$entityKey];
        }

        // Map static fields
        $classEntry = self::staticFields[get_class($object)] ?? [];
        foreach ($classEntry as $indexKey => $value) {
            $normalized[$indexKey] = $value;
        }

        $normalized['PK'] = $context['PK'];
        $normalized['SK'] = $context['SK'];
        $normalized['_t'] = get_class($object);

        $entities[] = $normalized;

        $context['SK'] = $context['SK'] . '/';
        foreach ($relations as $relationKey) {
            $method = 'get' . ucfirst($relationKey);
            $entity = $object->$method();
            $entities = array_merge($entities, $this->normalizeEntity($entity, $context));
        }

        return $entities;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        // if type contains '[]', then denormalize as an array like normal
        if (strpos($type, '[]') > 0) {
            return $this->denormalizeArray($data, $type, $context);
        }

        foreach ($data as $element) {
            if ($element['SK'] === $element['PK']) {
                $denormalized = $this->denormalizeObject($element, $element['_t'], $context);
                break;
            }
        }

        $this->denormalizeChildren($denormalized, $data, $context);

        return $denormalized;
    }

    private function denormalizeChildren($parent, $data, $context)
    {
        foreach ($data as $element) {
            $sk = $element['SK'];
            $parts = explode('/', $sk);
            $count = count($parts);
            if (count($parts) >= 2 && $parts[$count - 2] === $parent->getId()) {
                $child = $this->denormalizeObject($element, $element['_t'], $context);
                $this->denormalizeChildren($child, $data, $context);

                $rc = new ReflectionClass($parent);
                $methods = $rc->getMethods();
                $found = false;
                foreach ($methods as $method) {
                    $name = $method->getName();
                    if (strpos($name, 'set') !== 0 && strpos($name, 'add') !== 0) {
                        continue;
                    }

                    $parameters = $method->getParameters();
                    if (count($parameters) !== 1) {
                        continue;
                    }

                    /** @var ReflectionNamedType $type */
                    $type = $parameters[0]->getType();
                    if ($type->getName() === $element['_t']) {
                        $method->invoke($parent, $child);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $this->logger->error('Could not find method to set', $element);
                    die();
                }
            }
        }
    }

    private function denormalizeObject($data, $type, $context)
    {
        $this->normalizer->setSerializer($this->serializer);
        $denormalized = $this->normalizer->denormalize($data, $type, 'json', $context);
        return $denormalized;
    }

    private function denormalizeArray($data, $type, $context)
    {
        $this->arrayDenormalizer->setSerializer($this->serializer);
        $denormalized = $this->arrayDenormalizer->denormalize($data, $type, 'json', $context);
        return $denormalized;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $format === self::FORMAT;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return $format === self::FORMAT;
    }
}
