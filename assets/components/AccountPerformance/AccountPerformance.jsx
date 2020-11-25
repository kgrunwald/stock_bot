import { ArrowDownOutlined, ArrowUpOutlined } from '@ant-design/icons';
import { Card, Col, Divider, Row, Statistic } from 'antd';
import React from 'react';
import './AccountPerformance.less';

const AccountPerformance = () => {
    return (
        <Card
            style={{ width: '100%' }}
            title="Performance"
        >
            <Row justify="space-around">
                <Col>
                    <Statistic
                        title="Account Balances"
                        value={11.28}
                        precision={2}
                        valueStyle={{ color: '#3f8600' }}
                        prefix={<ArrowUpOutlined />}
                        suffix="%"
                    />
                </Col>
                <Col>
                    <Statistic
                        title="Effective Tax Rate"
                        value={9.3}
                        precision={2}
                        valueStyle={{ color: '#cf1322' }}
                        prefix={<ArrowDownOutlined />}
                        suffix="%"
                    />
                </Col>
            </Row>
            <Divider />
            <Col className="earnings-container">
                <Row justify="space-between" className="line">
                    <div>Total earned</div>
                    <div>$14,664.94</div>
                </Row>
                <Row justify="space-between" className="line">
                    <div>Dividends earned</div>
                    <div>$2,079.10</div>
                </Row>
            </Col>
        </Card>
    )
}

export default AccountPerformance;