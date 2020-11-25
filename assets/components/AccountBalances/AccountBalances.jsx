import { BankFilled, BankOutlined, DollarCircleFilled } from '@ant-design/icons';
import { Card, Col, Divider, Row, Space, Statistic, Typography } from 'antd';
import React from 'react';
import './AccountBalances.less';

const { Title } = Typography;

const AccountBalances = ({ accounts }) => {
    return (
        <Card
            style={{ width: '100%' }}
            title="Account Balances"
        >
            <Row justify="space-around">
                <Col>
                    <Statistic title="Account Balance" value="$54,234.59" precision={2} />
                </Col>
                <Col>
                    <Statistic title="Tax Fund" value="$4,234.59" precision={2} />
                </Col>
            </Row>
        </Card>
    )
}

export default AccountBalances;