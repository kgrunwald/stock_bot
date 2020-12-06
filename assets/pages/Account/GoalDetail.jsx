import { ArrowDownOutlined, ArrowUpOutlined, DollarCircleOutlined } from '@ant-design/icons';
import { Button, Card, Col, Divider, Layout, Row, Space, Statistic, Table, Typography } from 'antd';
import React from 'react';
import { VictoryLabel, VictoryPie } from 'victory';
import './GoalDetail.less';

const { Content } = Layout;
const { Title } = Typography;
const goal = { id: 2, name: 'Banana Stand', type: 'StockBot', balance: '$24,6320.43', drift: '-0.2%' };
let holdings = [
    { symbol: 'VTI', value: 1393.79, shares: 49, type: 'stock', class: 'Total US Market' },
    { symbol: 'VBR', value: 276.63, shares: 12, type: 'stock', class: 'US Small Cap' },
    { symbol: 'VEA', value: 1059.48, shares: 76, type: 'stock', class: 'Intl Developed Market' },
    { symbol: 'VWO', value: 1059.48, shares: 12, type: 'stock', class: 'Emerging Markets' },
    { symbol: 'VTIP', value: 265.97, shares: 7, type: 'bond', class: 'US Inflation-Protected Bonds' },
    { symbol: 'AGG', value: 799.18, shares: 14, type: 'bond', class: 'Total US Bond Market' },
    { symbol: 'Cash', value: 225.12, shares: '--', type: 'cash', class: 'Cash' },
];

const total = holdings.reduce((p, v) => { return p + v.value}, 0)
const allocation = {
    VTI: .2,
    VBR: .2,
    VEA: .2,
    VWO: .2,
    VTIP: .075,
    AGG: .075,
    Cash: .05
}

holdings = holdings.map((holding) => {
    const currentPercentage = holding.value / total;
    holding.drift = currentPercentage / allocation[holding.symbol];
    return holding;
});

const targetColors = holdings.map((holding) => {
    return holding.drift > 1.044 ? '#ffffff' : '#444444';
})



const columns = [
    {
        title: 'Symbol',
        dataIndex: 'symbol',
        key: 'symbol',
    },
    {
        title: 'Class',
        dataIndex: 'class',
        key: 'class'
    },
    {
        title: 'Shares',
        dataIndex: 'shares',
        key: 'shares',
        align: 'right',
    },
    {
        title: 'Market Value',
        dataIndex: 'value',
        key: 'value',
        align: 'right',
        render: function (text) {
            return `$${text}`;
        }
    }
];

const txns = [
    { date: '10/31/2020', symbol: 'VTI', side: 'Buy', shares: 49, price: 123.21, cost: 49 * 123.21 },
    { date: '11/01/2020', symbol: 'AGG', side: 'Sell', shares: 32, price: 117.21, cost: 32 * 117.21 },
]

const txnColumns = [
    {
        title: 'Date',
        dataIndex: 'date',
        key: 'date',
    },
    {
        title: 'Symbol',
        dataIndex: 'symbol',
        key: 'symbol',
    },
    {
        title: 'Side',
        dataIndex: 'side',
        key: 'side'
    },
    {
        title: 'Shares',
        dataIndex: 'shares',
        key: 'shares',
        align: 'right',
    },
    {
        title: 'Price',
        dataIndex: 'price',
        key: 'price',
        align: 'right',
        render: function (text) {
            return `$${text}`;
        }
    },
    {
        title: 'Cost',
        dataIndex: 'cost',
        key: 'cost',
        align: 'right',
        render: function (text) {
            return `$${text}`;
        }
    }
];

const GoalDetail = ({ onBack }) => {
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
                <Row justify="space-between" className="title-container">
                    <Col>
                        <Row>
                            <Space size={18}>
                                <DollarCircleOutlined style={{ color: 'darkblue', fontSize: 42, lineHeight: 2 }} />
                                <Col>
                                    <Title level={2}>{goal.name}</Title>
                                    <strong>Type:</strong> {goal.type}
                                </Col>
                            </Space>
                        </Row>
                    </Col>
                    <Col>
                        <Row>
                            <Space size={24}>
                                <Statistic title="Goal Balance" value="$54,234.59" precision={2} />
                                <Divider type="vertical" />
                                <Statistic title="StockBot Earnings" value="$4,234.59" precision={2} />
                                <Divider />
                                <Statistic
                                    title="Estimated Taxes"
                                    value="$1,312.45"
                                    precision={2}
                                />
                            </Space>
                        </Row>
                    </Col>
                </Row>
                <Row justify="space-between">
                    <Col display="flex" style={{ width: '57%' }}>
                        <Space size={32} direction="vertical" style={{ width: '100%' }} align="top">
                            <Card title="Holdings">
                                <Table dataSource={holdings} columns={columns} rowKey={(row) => row.symbol} pagination={false} />
                            </Card>
                            <Card
                                title="Transaction History"
                            >
                                <Table dataSource={txns} columns={txnColumns} rowKey={(row) => row.symbol} />
                            </Card>
                        </Space>
                    </Col>
                    <Col display="flex" style={{ width: '40%' }}>
                        <Space size={32} direction="vertical" style={{ width: '100%' }} align="top">
                            <Card title="Performance"
                                actions={[
                                    <Button type="primary" key="deposit">Allocate Cash</Button>,
                                    <Button>Transfer Funds</Button>,
                                    <Button>Withdraw</Button>,
                                  ]}
                            >
                                <Row justify="space-around">
                                    <Col>
                                        <Statistic
                                            title="30 Days"
                                            value={3.8}
                                            precision={2}
                                            valueStyle={{ color: '#cf1322' }}
                                            prefix={<ArrowDownOutlined />}
                                            suffix="%"
                                        />
                                    </Col>
                                    <Col>
                                        <Statistic
                                            title="YTD"
                                            value={4.3}
                                            precision={2}
                                            valueStyle={{ color: '#3f8600' }}
                                            prefix={<ArrowUpOutlined />}
                                            suffix="%"
                                        />
                                    </Col>
                                    <Col>
                                        <Statistic
                                            title="All Time"
                                            value={11.28}
                                            precision={2}
                                            valueStyle={{ color: '#3f8600' }}
                                            prefix={<ArrowUpOutlined />}
                                            suffix="%"
                                        />
                                    </Col>
                                </Row>
                            </Card>
                            <Card title="Current Allocation" style={{width: '100%', height: '35%'}}>
                                <div style={{position: 'relative', height: '100%', width: '100%'}}>
                                <VictoryPie
                                    height={250}
                                    animate={true}
                                    colorScale={['#002140', '#003a8c', '#1890ff', '#51A2D5', '#0AD48B', '#05AC72', '#218983']}
                                    radius={90}
                                    innerRadius={({datum}) => {
                                        const holding = holdings.find(holding => holding.symbol === datum.x);
                                        const drift = holding.drift > 2 ? 2 : holding.drift;
                                        const fillPercentage = drift / 2;
                                        return 20 + (60 - 60 * fillPercentage);
                                    }}
                                    labelComponent={<VictoryLabel style={{ fontFamily: 'inherit', fontSize: 12 }} />}
                                    data={holdings.map(holding => ({ x: holding.symbol, y: holding.value }))}
                                />
                                <VictoryPie
                                    style={{parent: {position: 'absolute', zIndex: '5', top: -3, left: 0, bottom: 0, right: 0, width: '100%', height: '100%'}}}
                                    height={250}    
                                    animate={true}
                                    colorScale={targetColors}
                                    radius={50}
                                    innerRadius={48}
                                    labels={() => {}}
                                    data={holdings.map(holding => ({ x: holding.symbol, y: holding.value }))}
                                />
                                {/* <div style={{display: 'flex', textAlign: 'center', alignItems: 'center', justifyContent: 'center', position: 'absolute', zIndex: '5', top: -3, left: 0, bottom: 0, right: 0, width: '100%', height: '100%'}}>
                                    Target<br/>Allocation
                                </div> */}
                                </div>
                            </Card>
                            <Card title="Tax Information">

                            </Card>
                        </Space>
                    </Col>
                </Row>
            </Content>
        </Layout>
    )
};

export default GoalDetail;