import { Col, Divider, Layout, Row, Space, Typography } from 'antd';
import React from 'react';
import GoalSummary from '../../components/GoalSummary/GoalSummary';
import AccountBalances from '../../components/AccountBalances/AccountBalances';
import AccountPerformance from '../../components/AccountPerformance/AccountPerformance';
import AccountPerformanceGraph from '../../components/AccountPerformanceGraph/AccountPerformanceGraph';
import AccountHoldings from '../../components/AccountHoldings/AccountHoldings';

const { Content } = Layout;
const { Title } = Typography;

const Home = ({ goals }) => {
    return (
        <Layout style={{ padding: '0 24px 24px' }}>
            <Content
                className="site-layout-background"
                style={{
                    padding: 32,
                    margin: 0,
                    minHeight: 280,
                    overflowY: 'auto'
                }}
            >
                <Title level={2}>Hello, Kyle</Title>
                <Divider />
                <Row justify="space-between" >
                    <Col style={{ width: '65%', display: 'flex' }} >
                        <Space size={32} direction="vertical" style={{ width: '100%' }} align="top">
                            <GoalSummary goals={goals} />
                            <AccountHoldings />
                        </Space>
                    </Col>
                    <Col style={{ width: '30%', display: 'flex' }}>
                        <Space size={32} direction="vertical" style={{ width: '100%' }} align="top">
                            <AccountBalances accounts={[
                                { id: 1, name: 'Account', balance: '$54,321.23' },
                                { id: 1, name: 'Taxes', balance: '$4,321.23' }
                            ]} />
                            <AccountPerformance />
                        </Space>
                    </Col>
                </Row>
            </Content>
        </Layout>
    )
};

export default Home;