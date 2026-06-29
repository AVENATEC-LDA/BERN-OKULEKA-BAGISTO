import { test as setup } from "../../../fixtures/test";
import { AdminLoginPage } from "../../../pages/admin/LoginPage";
import { AdminDashboardPage } from "../../../pages/admin/DashboardPage";
import { ADMIN_AUTH_STATE_PATH, ensureStateDir } from "../../../utils/paths";

setup(
    "Admin — authentication › authenticate as admin",
    async ({ page, env }) => {
        const login = new AdminLoginPage(page);
        await login.open();
        await login.submitCredentials(env.adminEmail, env.adminPassword);

        const dashboard = new AdminDashboardPage(page);
        await dashboard.waitUntilLoaded();

        ensureStateDir();
        await page.context().storageState({ path: ADMIN_AUTH_STATE_PATH });
    },
);
