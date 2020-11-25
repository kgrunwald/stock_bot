import { Col, Divider, Layout, Row, Space, Typography } from 'antd';
import React from 'react';
import GoalSummary from '../../components/GoalSummary/GoalSummary';
import AccountBalances from '../../components/AccountBalances/AccountBalances';
import AccountPerformance from '../../components/AccountPerformance/AccountPerformance';
import AccountPerformanceGraph from '../../components/AccountPerformanceGraph/AccountPerformanceGraph';

const { Content } = Layout;
const { Title } = Typography;

const GoalDetail = ({ goals }) => {
    return (
        <Title level={2}>Hello, Kyle</Title>
    )
};

export default GoalDetail;