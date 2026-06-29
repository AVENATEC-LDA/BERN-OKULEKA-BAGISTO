import { test, expect } from "../../fixtures/test";
import { HomePage } from "../../pages/shop/HomePage";

test.describe("Shop — homepage", () => {
    test("base url is reachable and serves the storefront", async ({
        page,
        env,
    }) => {
        const home = new HomePage(page);

        const response = await home.open();

        expect(
            response,
            `No response from ${env.baseUrl}. Is the server running and reachable?`,
        ).not.toBeNull();

        expect(
            response!.ok(),
            `Expected a 2xx response from ${env.baseUrl} but got HTTP ${response!.status()}.`,
        ).toBeTruthy();

        const finalUrl = new URL(page.url());
        const expectedHost = new URL(env.baseUrl).host;

        expect(
            finalUrl.host,
            `Expected to land on host "${expectedHost}" but ended up on "${finalUrl.host}".`,
        ).toBe(expectedHost);

        await expect(page.locator("body")).toBeVisible();
    });
});
