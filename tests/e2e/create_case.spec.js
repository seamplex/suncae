// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');

test('create new case from sample.step', async ({ page }) => {
    // Add network request/response logging
    page.on('request', request => {
        console.log('>>', request.method(), request.url());
    });

    page.on('response', async response => {
        const url = response.url();
        console.log('<<', response.status(), url);
        
        // Log responses from PHP scripts involved in CAD processing
        if (url.includes('import_cad.php') || url.includes('process.php')) {
            try {
                const body = await response.text();
                console.log('Response body:', body);
            } catch (e) {
                console.log('Could not read response body:', e.message);
            }
        }
    });

    // Add browser console logging
    page.on('console', msg => {
        console.log('BROWSER:', msg.type(), msg.text());
    });

    // 1. Go to /new
    await page.goto('/new/');

    // 2. In that view, there's a file upload input box. Put the file html/new/sample.step in the upload box
    // Note: path is relative to the test file.
    // tests/e2e/create_case.spec.js -> ../../html/new/sample.step
    const sampleFile = path.join(__dirname, '../../html/new/sample.step');
    
    // Wait for the import response
    const importResponsePromise = page.waitForResponse(response => 
        response.url().includes('import_cad.php') && response.status() === 200,
        { timeout: 30000 }
    );
    
    await page.setInputFiles('#cad', sampleFile);
    
    const importResponse = await importResponsePromise;
    const importBody = await importResponse.text();
    console.log('import_cad.php response:', importBody);

    // Wait for the process response
    const processResponse = await page.waitForResponse(response => 
        response.url().includes('process.php') && response.status() === 200,
        { timeout: 30000 }
    );
    
    const processBody = await processResponse.text();
    console.log('process.php response:', processBody);

    // Wait for the file to be processed and preview to be shown
    try {
        await expect(page.locator('#cad_preview')).toBeVisible({ timeout: 30000 });
    } catch (error) {
        // Check if there's an error message displayed
        const errorDiv = page.locator('#cad_error');
        if (await errorDiv.isVisible()) {
            const errorText = await errorDiv.textContent();
            console.log('CAD Error displayed:', errorText);
        }
        
        // Take a screenshot for debugging
        await page.screenshot({ path: 'test-results/upload-failure.png', fullPage: true });
        
        throw error;
    }

    // 3. Select "Solid mechanics" in the combo box for physics
    await page.selectOption('#physics', 'solid');

    // 4. Select "Mechanical elasticty" in the combo box for problem
    await page.selectOption('#problem', 'mechanical');

    // 5. Select "FeenoX" in the combo box for solver
    await page.selectOption('#solver', 'feenox');

    // 6. Select "Gmsh" in the combo box for mesher
    await page.selectOption('#mesher', 'gmsh');

    // Verify start button is enabled before clicking
    await expect(page.locator('#btn_start')).toBeEnabled();

    // 7. Click "Start"
    await page.click('#btn_start');

    // Optional: Verify we moved to the next step (e.g., URL changes to create.php or creating a case)
    // For now, just ensuring no error occurs immediately after click.
});
