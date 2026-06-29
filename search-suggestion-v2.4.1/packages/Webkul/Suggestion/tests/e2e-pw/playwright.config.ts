import { defineConfig, devices } from "@playwright/test";
import { env } from "./utils/env";
import { ADMIN_AUTH_STATE_PATH } from "./utils/paths";

export default defineConfig({
    testDir: "./tests",

    timeout: 60_000,

    expect: { timeout: 10_000 },

    outputDir: "./test-results",

    fullyParallel: false,

    workers: 1,

    forbidOnly: !!process.env.CI,

    reporter: [
        ["list"],
        [
            "html",
            {
                outputFolder: "./playwright-report",
                open: "never",
            },
        ],
    ],

    use: {
        ...devices["Desktop Chrome"],
        baseURL: `${env.baseUrl}/`,
        actionTimeout: env.actionTimeout,
        navigationTimeout: env.navigationTimeout,
        headless: !env.headed,
        screenshot: { mode: "only-on-failure", fullPage: true },
        trace: "retain-on-failure",
    },

    projects: [
        {
            name: "admin-setup",
            testMatch: "admin/**/*.setup.ts",
        },
        {
            name: "admin",
            testMatch: "admin/**/*.spec.ts",
            dependencies: ["admin-setup"],
            use: {
                storageState: ADMIN_AUTH_STATE_PATH,
            },
        },
        {
            name: "shop",
            testMatch: "shop/**/*.spec.ts",
            dependencies: ["admin"],
        },
    ],
});
