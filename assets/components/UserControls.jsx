import React, { useEffect, useState } from 'react';
import { Avatar, Button, Menu, Space } from 'antd';
import { UserOutlined} from '@ant-design/icons';


const UserControls = () => {
    const [user, setUser] = useState(null);

    const signIn = async () => {
        const res = await fetch("/auth/login", { method: 'post' });
        const data = await res.json();
        window.location.assign(data.url);
    }

    const signOut = async () => {
        await fetch("/auth/logout", { method: 'post' });
        window.location.assign("/"); // hard reload to home page
    }

    const handleClick = async () => {
        if (!!user) {
            return await signOut();
        }

        await signIn();
    }

    useEffect(async () => {
        await fetch('/api/plans');
        const res = await fetch("/api/user");
        if (res.status === 200) {
            await setUser(await res.json());
        }
    }, []);

    return (
        <>
            <Space size={12}>
                {user && <Avatar icon={<UserOutlined />} />}
                <Button ghost onClick={handleClick}>{!!user ? 'Sign Out' : 'Sign In'}</Button>
            </Space>
        </>
    );

}

export default UserControls;