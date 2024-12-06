import { useEffect, useRef, useState } from 'react';
import * as React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFloppyDisk,
    faInfoCircle,
    faMemory,
    faMicrochip,
    faPowerOff,
    faXmarkCircle,
    IconDefinition,
} from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServerResourceUsage, { ServerPowerState, ServerStats } from '@/api/server/getServerResourceUsage';
import { useStoreState } from '@/state/hooks';
import classNames from 'classnames';

export function statusToColor(state?: ServerPowerState): string {
    switch (state) {
        case 'running':
            return 'text-green-500';
        case 'starting':
        case 'stopping':
            return 'text-yellow-500';
        default:
            return 'text-red-500';
    }
}

const UtilBox = ({
    utilised,
    icon,
    rounded,
    server,
}: {
    utilised: number;
    icon: IconDefinition;
    rounded?: string;
    server?: Server;
}) => {
    return (
        <div
            className={classNames(
                'w-full h-full bg-white/10 shadow-xl m-auto px-4 py-2',
                rounded === 'left' && 'rounded-l-lg',
                rounded === 'right' && 'rounded-r-lg',
                rounded === 'full' && 'rounded-lg col-span-3',
            )}
        >
            <div className={'text-gray-300 font-bold text-center'}>
                <p className={'my-auto inline-flex text-sm'}>
                    <FontAwesomeIcon icon={icon} className={'my-auto mr-1'} size={'xs'} />
                    <p className={'my-auto'}>
                        {utilised > -1
                            ? `${utilised}%`
                            : `Server is ${server?.isTransferring ? 'transferring' : server?.status ?? 'offline'}`}
                    </p>
                </p>
            </div>
        </div>
    );
};

type Timer = ReturnType<typeof setInterval>;

export default ({ server }: { server: Server }) => {
    const [stats, setStats] = useState<ServerStats>();
    const colors = useStoreState(state => state.theme.data!.colors);
    const interval = useRef<Timer>(null) as React.MutableRefObject<Timer>;
    const [isSuspended, setIsSuspended] = useState(server.status === 'suspended');

    const getStats = () =>
        getServerResourceUsage(server.uuid)
            .then(data => setStats(data))
            .catch(error => console.error(error));

    useEffect(() => {
        setIsSuspended(stats?.isSuspended || server.status === 'suspended');
    }, [stats?.isSuspended, server.status]);

    useEffect(() => {
        // Don't waste a HTTP request if there is nothing important to show to the user because
        // the server is suspended.
        if (isSuspended) return;

        getStats().then(() => {
            interval.current = setInterval(() => getStats(), 30000);
        });

        return () => {
            interval.current && clearInterval(interval.current);
        };
    }, [isSuspended]);

    const cpuUsed =
        server.limits.cpu === 0 ? stats?.cpuUsagePercent : (stats?.cpuUsagePercent ?? 0) / (server.limits.cpu / 100);
    const diskUsed = ((stats?.diskUsageInBytes ?? 0) / 1024 / 1024 / server.limits.disk) * 100;
    const memoryUsed = ((stats?.memoryUsageInBytes ?? 0) / 1024 / 1024 / server.limits.memory) * 100;

    return (
        <>
            <Link to={`/server/${server.id}`}>
                <div
                    className={
                        'w-full p-4 rounded-lg grid grid-cols-12 mb-2 hover:brightness-150 transition duration-300'
                    }
                    style={{ backgroundColor: colors.background }}
                >
                    <FontAwesomeIcon
                        className={classNames(statusToColor(stats?.status ?? 'offline'), 'my-auto ml-4')}
                        icon={!stats?.isSuspended ? faPowerOff : faXmarkCircle}
                        size={'lg'}
                    />
                    <div className="whitespace-nowrap text-white col-span-7">
                        {server.name}
                        <div className={'text-gray-500 text-xs my-auto'}>
                            {server.allocations[0]?.ip.toString()}:{server.allocations[0]?.port.toString()}
                        </div>
                    </div>
                    {stats?.status === 'offline' ? (
                        <UtilBox rounded={'full'} utilised={-1} icon={faInfoCircle} server={server} />
                    ) : (
                        <>
                            <UtilBox rounded={'left'} utilised={Number(cpuUsed?.toFixed(0))} icon={faMicrochip} />
                            <UtilBox utilised={Number(memoryUsed.toFixed(0))} icon={faMemory} />
                            <UtilBox rounded={'right'} utilised={Number(diskUsed.toFixed(0))} icon={faFloppyDisk} />
                        </>
                    )}
                </div>
            </Link>
        </>
    );
};
