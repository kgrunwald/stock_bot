import React from 'react';
import { Row, Col, Menu, Typography, Space } from 'antd';
import { RobotOutlined } from '@ant-design/icons';
import UserControls from './UserControls';

const { Title } = Typography;

const AppBar = () => (
    <Row justify="space-between" align="middle">
        <Col justify="center" style={{ marginLeft: -24 }}>
            <Row>
                <Space style={{ marginTop: 4 }}>
                    <RobotOutlined style={{ color: "white", fontSize: 24 }} />
                    <Title level={4} style={{ color: 'white', marginTop: 4 }}>Stockbot</Title>
                </Space>
            </Row>
        </Col>
        <Col>
            <Row>
                <Menu theme="dark" mode="horizontal" style={{ float: 'right' }} defaultSelectedKeys={['2']}></Menu>
                <UserControls />
            </Row>
        </Col>
    </Row>
)

export default AppBar;