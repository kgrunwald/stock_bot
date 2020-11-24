import { Button, Layout, Menu } from 'antd';
import React, { Component } from 'react';

const { Header, Content, Footer } = Layout;


class HomePage extends Component {
    signIn = async () => {
        const res = await fetch("/auth/login", { method: 'post' });
        const data = await res.json();
        window.location.assign(data.url);
    }

    render() {
        return (
            <Layout>
                <Header>
                    <div className="logo" />
                    <Menu theme="dark" mode="horizontal" style={{float: 'right' }} defaultSelectedKeys={['2']}>
                        <Button ghost onClick={this.signIn}>Sign In</Button>
                    </Menu>
                </Header>
            </Layout>
        )
    }
}

export default HomePage;