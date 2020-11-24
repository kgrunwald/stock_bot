import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import HomePage from './pages/HomePage'
import AccountPage from './pages/AccountPage';

import 'antd/dist/antd.css';


const App = () => {
    return (
        <Switch>
            <Route path="/" exact component={HomePage} />
            <Route path="/account" component={AccountPage} />
        </Switch>
    )
}


ReactDOM.render(
    <Router>
        <App />
    </Router>, 
document.getElementById('root'));
