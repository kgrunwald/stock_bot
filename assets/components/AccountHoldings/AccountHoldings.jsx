import { Card, Table, Tag } from 'antd';
import React from 'react';

const data = [{
    key: 1,
    symbol: 'TQQQ',
    shares: 399,
    value: '$83,214.21',
    change: '+82%',
    type: 'stock'
}, {
    key: 2,
    symbol: 'AGG',
    shares: 23,
    value: '$3,214.21',
    change: '+2%',
    type: 'bond'
},{
    key: 3,
    symbol: 'Cash',
    value: '$5,214.21',
    shares: '--',
    type: 'cash',
    change: '--'
}];

const columns = [
    {
      title: 'Symbol',
      dataIndex: 'symbol',
      key: 'symbol',
    },
    {
        title: 'Type',
        dataIndex: 'type',
        key: 'type',
        render: function(text) {
            if (text === 'stock') {
                return <Tag color='geekblue' key='stock'>STOCK</Tag>
            }
            if (text === 'bond') {
                return <Tag color='volcano' key='stock'>BOND</Tag>
            }
            return <Tag color='green' key='stock'>CASH</Tag>
        }
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
      align: 'right'
    },
    {
        title: 'Performance',
        dataIndex: 'change',
        key: 'change',
        align: 'right',
        render: function(text) {
            if (text === '--') {
                return text;
            }
            if (text.indexOf('-') >= 0) {
                return <span style={{color: 'red'}}>{text}</span>
            }
            return <span style={{color: 'green'}}>{text}</span>
        }
      },
  ];

const AccountHoldings = () => {
    return (
        <Card
            title="Holdings"
        >
            <Table dataSource={data} columns={columns} />
        </Card>
    );
}

export default AccountHoldings;