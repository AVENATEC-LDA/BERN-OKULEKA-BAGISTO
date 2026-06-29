import { test as base, expect } from "@playwright/test";
import { env, type Env } from "../utils/env";

type Fixtures = {
    env: Env;
};

export const test = base.extend<Fixtures>({
    env: async ({}, use) => {
        await use(env);
    },

    page: async ({ page }, use) => {
        await page.addInitScript(() => {
            const injectHideRule = () => {
                const style = document.createElement("style");
                style.textContent =
                    ".phpdebugbar { display: none !important; }";
                (document.head ?? document.documentElement).appendChild(style);
            };

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", injectHideRule);
            } else {
                injectHideRule();
            }
        });

        await use(page);
    },
});

export { expect };
