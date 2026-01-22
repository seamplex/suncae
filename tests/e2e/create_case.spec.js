// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');

test('create new case from sample.step', async ({ page }) => {
    // 1. Go to /new
    await page.goto('/new/');

    // 2. In that view, there's a file upload input box. Put the file html/new/sample.step in the upload box
    // Note: path is relative to the test file.
    // tests/e2e/create_case.spec.js -> ../../html/new/sample.step
    const sampleFile = path.join(__dirname, '../../html/new/sample.step');
    await page.setInputFiles('#cad', sampleFile);

    // Wait for the file to be processed and preview to be shown
    await expect(page.locator('#cad_preview')).toBeVisible({ timeout: 30000 });

    // 3. Select "Solid mechanics" in the combo box for physics
    await page.selectOption('#physics', 'solid');

    // 4. Select "Mechanical elasticty" in the combo box for problem
    await page.selectOption('#problem', 'mechanical');

    // 5. Select "FeenoX" in the combo box for solver
    await page.selectOption('#solver', 'feenox');

    // 6. Select "Gmsh" in thecombo box for mesher
    await page.selectOption('#mesher', 'gmsh');

    // Verify start button is enabled before clicking
    await expect(page.locator('#btn_start')).toBeEnabled();

    // 7. Click "Start"
    await page.click('#btn_start');

    // Optional: Verify we moved to the next step (e.g., URL changes to create.php or creating a case)
    // For now, just ensuring no error occurs immediately after click.
});
