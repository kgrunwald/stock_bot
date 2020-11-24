import React from 'react';
import { Route, Switch } from 'react-router-dom';
import HomePage from './pages/HomePage'
import AccountPage from './pages/AccountPage';

export default () => {
    return (
        <Switch>
            <Route path="/" exact component={HomePage} />
            <Route path="/account" component={AccountPage} />
        </Switch>
    )
}