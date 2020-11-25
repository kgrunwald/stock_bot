import { Layout, Typography } from 'antd';
import React from 'react';
import AppBar from '../components/AppBar';
import GoalsMenu from '../components/GoalsMenu/GoalsMenu';
import Home from './Account/Home';
import { Route, Switch } from 'react-router-dom';
import GoalDetail from './Account/GoalDetail';

const { Header, Content, Sider } = Layout;
const { Title } = Typography;

const goals = [
    { id: 1, name: 'Test Goal', type: '3% Signal', balance: '$12,420.12', drift: '3.2%' },
    { id: 2, name: 'Banana Stand', type: 'StockBot', balance: '$24,6320.43', drift: '-0.2%' },
    { id: 3, name: 'Vegas Fund', type: 'StockBot', balance: '$7,324.42', drift: '-0.2%' }
];

const AccountPage = ({ history }) => {
    function handleGoalClick(id) {
        history.push(`/account/goal/${id}`);
    }

    function handleHomeClick() {
        history.push("/account");
    }

    return (
        <Layout style={{ height: '100%' }}>
            <Header style={{ height: 'auto' }}>
                <AppBar />
            </Header>
            <Layout>
                <Sider width={250} className="site-layout-background">
                    <GoalsMenu goals={goals} onHomeClick={handleHomeClick} onGoalClick={handleGoalClick}/>
                </Sider>
                <Switch>
                    <Route exact path="/account" component={() => <Home goals={goals} />} />
                    <Route path="/account/goal" component={() => <GoalDetail goals={goals} />} />
                </Switch>
            </Layout>
        </Layout>
    )
};

export default AccountPage;