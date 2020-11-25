import { Card } from 'antd';
import React from 'react';
import { VictoryTheme, VictoryChart, VictoryLine } from 'victory';
import { theme } from './theme';

const AccountPerformanceGraph = () => {
    return (
        <Card
            title="Performance"
        >
            <VictoryChart
                theme={theme}
                style={{ parent: { maxWidth: "100%" } }}
                width={700} height={250}
            >
                <VictoryLine
                    data={[
                        { x: 1, y: 10000 },
                        { x: 2, y: 12000 },
                        { x: 3, y: 11000 },
                        { x: 4, y: 11500 },
                        { x: 5, y: 12500 },
                        { x: 5, y: 13000 },
                    ]}
                />
            </VictoryChart>
        </Card>
    );
};

export default AccountPerformanceGraph;