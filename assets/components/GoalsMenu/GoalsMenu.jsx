import React from 'react';
import { DollarCircleOutlined, HomeOutlined } from "@ant-design/icons";
import { Divider, Menu } from "antd";

const GoalsMenu = ({ goals, onHomeClick, onGoalClick }) => {
    function handleClick({ key }) {
        if (key === 'home') {
            onHomeClick();
        } else {
            onGoalClick(key)
        }
    }

    return (
        <>
        <Menu
            mode="inline"
            style={{ height: '100%', borderRight: 0 }}
            defaultSelectedKeys={["home"]}
            onClick={handleClick}
        >
            <Menu.Item key="home" icon={<HomeOutlined />}>
                Home
            </Menu.Item>
            <Menu.Divider />
            {goals.map(goal => (
                <Menu.Item key={goal.id} icon={<DollarCircleOutlined />}>
                    {goal.name}
                </Menu.Item>
            ))}
        </Menu>
        <Divider/>
        </>
    );
}

export default GoalsMenu;