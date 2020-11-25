import { Layout } from 'antd';
import React from 'react';
import AppBar from '../components/AppBar';
import GoalsMenu from '../components/GoalsMenu/GoalsMenu';

const { Header, Sider } = Layout;

const PageLayout = ({goals, children}) => {
    return (
        <Layout style={{ height: '100%' }}>
            <Header style={{ height: 'auto' }}>
                <AppBar />
            </Header>
            <Layout>
                <Sider width={250} className="site-layout-background">
                    <GoalsMenu goals={goals} />
                </Sider>
                {children}
            </Layout>
        </Layout>
    )
};

export default PageLayout;