import { Button, Layout, Menu } from 'antd';
import React, { Component } from 'react';
import { Redirect } from 'react-router-dom';

const { Header, Content, Footer } = Layout;


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
            <Layout>
                <Header>
                    <div className="logo" />
                    <Menu theme="dark" mode="horizontal" style={{float: 'right' }} defaultSelectedKeys={['2']}>
                        <Button ghost onClick={this.signOut}>Sign Out</Button>
                    </Menu>
                </Header>
            </Layout>
        )
    }
}

export default AccountPage;