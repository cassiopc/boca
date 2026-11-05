//========================================================================
// Copyright Universidade Federal do Espirito Santo (Ufes)
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
// 
// This program is released under license GNU GPL v3+ license.
//
//========================================================================

import { test, expect } from '@playwright/test';

import { 
  checkLoginPage,
  checkContestPage
} from "./setup";

test.describe.serial("Login test scenarios", () => {

  test.use({ storageState: 'playwright/.auth/anonymous.json' });

  test.beforeEach(async ({ page }) => {
    // Register a dialog handler before authentication fails
    page.on('dialog', async dialog => {
      // Verify type of dialog
      expect(dialog.type()).toContain('alert');

      // Verify dialog message
      expect([
        'User does not exist or incorrect password.',
        'Incorrect password.'
      ]).toContain(dialog.message());

      // Click on OK Button
      try {
        await dialog.accept();
      } catch(error) {
        console.error(`${error}`);
      }
    });

    // Go to the starting url before each test
    await page.goto('/boca');

    // Check if this is the login page
    await checkLoginPage(page);
  });

  test.describe("Negative testing", () => {

    test('Missing username', async ({ page }) => {
      // Perform authentication steps
      await page.locator('input[name="name"]').fill('');
      await page.locator('input[name="password"]').fill('boca');
      await page.getByRole('button', { name: 'Login' }).click();
      // Wait until the page receives the cookies

      // Check if redirected back to login page
      await checkLoginPage(page);

      // End of authentication steps
    });

    test('Missing password', async ({ page }) => {
      // Perform authentication steps
      await page.locator('input[name="name"]').fill('system');
      await page.locator('input[name="password"]').fill('');
      await page.getByRole('button', { name: 'Login' }).click();
      // Wait until the page receives the cookies

      // Check if redirected back to login page
      await checkLoginPage(page);

      // End of authentication steps
    });

    test('Login fails as admin user', async ({ page }) => {
      // Perform authentication steps
      await page.locator('input[name="name"]').fill('admin');
      await page.locator('input[name="password"]').fill('boca');
      await page.getByRole('button', { name: 'Login' }).click();
      // Wait until the page receives the cookies

      // Check if redirected back to login page
      await checkLoginPage(page);

      // End of authentication steps
    });

    test('Login fails as team user', async ({ page }) => {
      // Perform authentication steps
      await page.locator('input[name="name"]').fill('team1');
      await page.locator('input[name="password"]').fill('boca');
      await page.getByRole('button', { name: 'Login' }).click();
      // Wait until the page receives the cookies

      // Check if redirected back to login page
      await checkLoginPage(page);

      // End of authentication steps
    });

    test('Login fails as judge user', async ({ page }) => {
      // Perform authentication steps
      await page.locator('input[name="name"]').fill('judge1');
      await page.locator('input[name="password"]').fill('boca');
      await page.getByRole('button', { name: 'Login' }).click();
      // Wait until the page receives the cookies

      // Check if redirected back to login page
      await checkLoginPage(page);

      // End of authentication steps
    });

  });

  test.describe("Positive testing", () => {

    test("Has title", async ({ page }) => {
      // Expect title "to contain" a substring
      await expect(page).toHaveTitle(/Login/);
    });
  
    test("Has text", async ({ page }) => {
      // Expect page "to contain" a string
      await expect(page.getByText('BOCA Login') !== undefined).toBeTruthy();
    });
  
    test("Has input fields", async ({ page }) => {
      // Expect page to have input fields
      await expect(page.locator('input[name="name"]') !== undefined)
        .toBeTruthy();
      await expect(page.locator('input[name="password"]') !== undefined)
        .toBeTruthy();
    });
  
    test("Has button", async ({ page }) => {
      // Expect page to have a button
      await expect(page.getByRole('button', { name: 'Login' }) !== undefined)
        .toBeTruthy();
    });
  
    test('Login as system user', async ({ page }) => {
      // Fill in user credentials
      await page.locator('input[name="name"]').fill('system');
      await page.locator('input[name="password"]').fill('boca');
      await page.getByRole('button', { name: 'Login' }).click();
      // Wait until the page receives the cookies

      // Check if redirected to contest page
      await checkContestPage(page);

      // End of authentication steps
    });

  });

});
