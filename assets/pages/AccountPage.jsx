import { Avatar, Button, Col, Layout, Menu, Row, Space, Typography } from 'antd';
import React, { Component } from 'react';
import {HomeOutlined, RobotOutlined, StockOutlined, UserOutlined} from '@ant-design/icons';
import { Redirect } from 'react-router-dom';

const { Header, Content, Sider, Footer } = Layout;
const { Title } = Typography;


class AccountPage extends Component {
    constructor(props) {
        super(props);
        this.state = { loginError: null };
    }

    async componentDidMount() {
        const res = await fetch("/api/user");
        if (res.status !== 200) {
            this.setState({
                ...this.state,
                loginError: true,
            })
        }
    }

    signOut = async () => {
        await fetch("/auth/logout", { method: 'post' });
        window.location.assign("/"); // hard reload to home page
    }

    render() {
        if (this.state.loginError) {
            return <Redirect to="/" />
        }

        return (
            <Layout style={{height: '100%'}}>
                <Header style={{height: 'auto'}}>
                    <Row justify="space-between" align="middle">
                        <Col justify="center" style={{marginLeft: -24}}>
                            <Row>
                            <Space style={{marginTop: 4}}>
                                <RobotOutlined style={{color:"white", fontSize: 24}} />
                                <Title level={4} style={{color: 'white', marginTop: 4}}>Stockbot</Title>
                            </Space>
                            </Row>
                        </Col>
                        <Col>
                            <Row>
                            <Menu theme="dark" mode="horizontal"></Menu>
                            <Space size={12}>
                                <Avatar size="large" icon={<UserOutlined />} />
                                <Button ghost onClick={this.signOut}>Sign Out</Button>
                            </Space>
                            </Row>
                        </Col>
                    </Row>
                </Header>
                <Layout>
                    <Sider width={200} className="site-layout-background">
                        <Menu
                            mode="inline"
                            style={{ height: '100%', borderRight: 0 }}
                            defaultSelectedKeys={["1"]}
                        >
                            <Menu.Item key="1" icon={<HomeOutlined/>}>
                                Home
                            </Menu.Item>
                        </Menu>
                    </Sider>
                    <Layout style={{ padding: '0 24px 24px' }}>
                    <Content
                        className="site-layout-background"
                        style={{
                            padding: 24,
                            margin: 0,
                            minHeight: 280,
                        }}
                    >
                        Content
                     </Content>
                </Layout>
                </Layout>
            </Layout>
        )
    }
}

export default AccountPage;