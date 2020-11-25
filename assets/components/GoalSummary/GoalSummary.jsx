import { BankFilled, BankOutlined } from '@ant-design/icons';
import { Card, Col, Divider, Row, Space, Typography } from 'antd';
import React from 'react';
import './GoalSummary.less';

const { Title } = Typography;

const GoalSummary = ({ goals }) => {
    return (
        <Card
            style={{ width: '100%' }}
            title="Goals"
        >
            {goals.map(goal => (
                <div key={goal.id}>
                <Row align="middle" justify="space-between">
                    <Col>
                        <Row align="middle">
                            <Space>
                                <Col className="goal-icon">
                                    <BankFilled style={{color: 'inherit'}}/>
                                </Col>
                                <Col>
                                    <div style={{ fontWeight: 'bold' }}>{goal.name}</div>
                                    <div >{goal.type}</div>
                                </Col>
                            </Space>
                        </Row>
                    </Col>
                    <Col>
                        <Row align="middle" justify="end">
                            <Col>
                                <div>{goal.balance}</div>
                                <div style={{ float: 'right', color: 'green' }}>{goal.drift}</div>
                            </Col>
                        </Row>
                    </Col>
                </Row>
                <Divider />
                </div>
            ))}
        </Card>
    )
}

export default GoalSummary;