import { Layout, Row, Menu } from 'antd';
import React, { Component } from 'react';
import AppBar from '../components/AppBar';

const { Header } = Layout;


class HomePage extends Component {
    render() {
        return (
            <Layout>
                <Header>
                    <AppBar />
                </Header>
            </Layout>
        )
    }
}

export default HomePage;