import { UserOutlined } from '@ant-design/icons';
import { Layout, Row, Menu, Card, Input, Button, Form } from 'antd';
import { Content } from 'antd/lib/layout/layout';
import React, { Component, useState } from 'react';
import AppBar from '../components/AppBar';

const { Header } = Layout;

const layout = {
    labelCol: { span: 6 },
    wrapperCol: { span: 16 },
};

const tailLayout = {
    wrapperCol: { offset: 8, span: 16 },
};

const RegisterPage = ({ history }) => {
    const [form] = Form.useForm();
    const onFinish = async values => {
        const res = await fetch('/api/register', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(values)})
        const data = await res.json();
        if(res.status === 200) {
            history.replace(data.url)
            return;
        }
    };

    const onFinishFailed = errorInfo => {
        console.log('Failed:', errorInfo);
    };

    return (
        <Layout style={{ height: '100%', display: 'flex', alignItems: 'center' }}>
            <Header style={{ width: '100%' }}>
                <AppBar />
            </Header>
            <Content style={{ width: '30%', margin: 24 }}>
                <Card title="Register Account">
                <Form
                        {...layout}
                        name="basic"
                        initialValues={{ remember: true }}
                        onFinish={onFinish}
                        onFinishFailed={onFinishFailed}
                        form={form}
                    >
                        <Form.Item
                            label="First Name"
                            name="name"
                            rules={[{ required: true, message: 'Please input first name' }]}
                        >
                            <Input />
                        </Form.Item>
                        <Form.Item
                            label="Email"
                            name="email"
                            rules={[
                                {
                                  type: 'email',
                                  message: 'The input is not valid email',
                                },
                                {
                                  required: true,
                                  message: 'Please input your email',
                                },
                              ]}
                        >
                            <Input />
                        </Form.Item>
                        <Form.Item
                            label="Alpaca API Token"
                            name="token"
                            rules={[{ required: true, message: 'Please input your Alpaca API token' }]}
                        >
                            <Input />
                        </Form.Item>
                        <Form.Item {...tailLayout}>
                            <Button type="primary" htmlType="submit">
                                Register
                            </Button>
                        </Form.Item>
                    </Form>
                </Card>
            </Content>
        </Layout>
    )
}

export default RegisterPage;