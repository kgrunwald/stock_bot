import { Layout, Typography } from 'antd';
import React, { useEffect, useState } from 'react';
import AppBar from '../components/AppBar';
import GoalsMenu from '../components/GoalsMenu/GoalsMenu';
import Home from './Account/Home';
import { Route, Switch } from 'react-router-dom';
import GoalDetail from './Account/GoalDetail';

const { Header, Sider } = Layout;

const AccountPage = ({history}) => {
    const [goals, setGoals] = useState([]);
    const [goal, setGoal] = useState(null);

    async function handleGoalClick(id) {
        await setGoal(goals[id - 1]);
        history.push(`/account/goal/${id}`);
    }

    function handleHomeClick() {
        history.push("/account");
    }

    useEffect(async () => {
        const goals = await fetch('/api/goals');
        if (goals.status === 200) {
            setGoals(await goals.json());
        }
    }, []);

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
                    <Route path="/account/goal" component={() => <GoalDetail goal={goal} onBack={() => history.goBack()} />} />
                </Switch>
            </Layout>
        </Layout>
    )
};

export default AccountPage;