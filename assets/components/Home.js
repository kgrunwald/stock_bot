// ./assets/js/components/Home.js

import React, { Component } from 'react';
import { Route, Switch, Redirect, Link, withRouter } from 'react-router-dom';
import Users from './Users';
import Posts from './Posts';

class Home extends Component {
    constructor(props) {
        super(props);
        this.state = { user: null };
    }

    async componentDidMount() {
        const res = await fetch("/api/user");
        if (res.status === 200) {
            const data = await res.json();
            if (data) {
                this.setState({
                    ...this.state,
                    user: data
                });
            }
        }
    }

    signIn = async () => {
        const res = await fetch("/auth/login", { method: 'post' });
        const data = await res.json();
        window.location.assign(data.url);
    }

    signOut = async () => {
        await fetch("/auth/logout", { method: 'post' });
        window.location.assign("/");
    }

    render() {
        return (
            <div>
                {this.state.user && <button onClick={this.signOut}>sign out</button>}
                {!this.state.user && <button onClick={this.signIn}>sign in</button>}

                <Link to="/users"><button>Users</button></Link>
                <Switch>
                    {/* <Redirect exact from="/" to="/users" /> */}
                    <Route path="/users" component={Users} />
                    <Route path="/posts" component={Posts} />
                </Switch>
            </div>
        )
    }
}

export default Home;