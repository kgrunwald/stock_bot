// ./src/js/app.js
    
import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import './styles/app.css';
import Home from './components/Home';
    
ReactDOM.render(<Router><Home /></Router>, document.getElementById('root'));
