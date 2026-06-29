import { test, expect } from "../../../fixtures/test";
import { AdminDashboardPage } from "../../../pages/admin/DashboardPage";

test.describe("Admin — dashboard", () => {
    test("dashboard page loads", async ({ page }) => {
        const dashboard = new AdminDashboardPage(page);
        await dashboard.open();
        await dashboard.waitUntilLoaded();

        expect(
            page.url(),
            `Expected to land on /admin/dashboard but ended up on ${page.url()}.`,
        ).toMatch(/\/admin\/dashboard\/?$/);

        await expect(page.locator("body")).toBeVisible();
    });
});
