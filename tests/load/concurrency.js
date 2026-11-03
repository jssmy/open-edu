import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');

export const options = {
    scenarios: {
        concurrent_login_page_visitors: {
            executor: 'constant-vus',
            vus: 500,
            duration: '1m',
            gracefulStop: '10s',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<2000'],
        checks: ['rate>0.99'],
    },
};

export default function () {
    const response = http.get(`${baseUrl}/login`, {
        tags: { page: 'login' },
    });

    check(response, {
        'login page responds successfully': (result) => result.status === 200,
    });
    sleep(1);
}
