// @ts-check
const { test, expect } = require('@playwright/test');

test('new-case CAD import rejects requests without POST CSRF', async ({ request }) => {
    const getResponse = await request.get('/new/import_cad.php');
    expect(getResponse.ok()).toBeTruthy();
    await expect(getResponse.json()).resolves.toMatchObject({
        status: 'error',
        error: 'invalid request method'
    });

    const postResponse = await request.post('/new/import_cad.php');
    expect(postResponse.ok()).toBeTruthy();
    await expect(postResponse.json()).resolves.toMatchObject({
        status: 'error',
        error: 'invalid CSRF token'
    });
});