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

import { test as setup, expect, Page } from '@playwright/test';

const emptyAuthFile = 'playwright/.auth/anonymous.json';
const sysAuthFile = 'playwright/.auth/system.json';

setup('Authenticate as anonymous', async ({ page }) => {
  // Perform authentication steps
  await page.goto('/boca');
  // Wait until the page receives the cookies

  // End of authentication steps
  await page.context().storageState({ path: emptyAuthFile });
});

setup('Authenticate as system', async ({ page }) => {
  // Perform authentication steps
  await page.goto('/boca');
  await page.locator('input[name="name"]').fill('system');
  await page.locator('input[name="password"]').fill('boca');
  await page.getByRole('button', { name: 'Login' }).click();
  // Wait until the page receives the cookies

  // End of authentication steps
  await page.context().storageState({ path: sysAuthFile });
});

const checkLoginPage = async (
  page: Page
): Promise<undefined> => {
    // Expect title "to contain" a substring
    await expect(page).toHaveTitle(/Login/) != undefined;
    // Expect page "to contain" a string
    await expect(page.getByText('BOCA Login') !== undefined).toBeTruthy();
    // Expect page to have input fields
    await expect(page.locator('input[name="name"]') !== undefined)
      .toBeTruthy();
    await expect(page.locator('input[name="password"]') !== undefined)
      .toBeTruthy();
    // Expect page to have a button
    await expect(page.getByRole('button', { name: 'Login' }) !== undefined)
      .toBeTruthy();
}

const checkContestPage = async (
  page: Page
): Promise<undefined> => {
    // Expect title "to contain" a substring
    await expect(page).toHaveTitle(/System's/) != undefined;
    // Expect page to have links
    await expect(
      page.getByRole('link', { name: 'Contest' }) !== undefined
    ).toBeTruthy();
    await expect(
      page.getByRole('link', { name: 'Options' }) !== undefined
    ).toBeTruthy();
    await expect(
      page.getByRole('link', { name: 'Logout' }) !== undefined
    ).toBeTruthy();
};

export {
  checkLoginPage,
  checkContestPage,
}
