import { test, expect } from "../../../../fixtures/test";
import { SuggestionConfigPage } from "../../../../pages/admin/configuration/suggestion/SuggestionConfigPage";
import enabledConfig from "../../../../data/configuration/suggestion/enabled.json";

test.describe("Admin — configuration › suggestion", () => {
    test("enable suggestion module with desired settings", async ({ page }) => {
        const config = new SuggestionConfigPage(page);

        await config.open();
        await config.applyConfiguration(enabledConfig);

        await config.open();

        await expect(
            page.locator(
                'input[type="checkbox"][name="suggestion[suggestion][general][status]"]',
            ),
        ).toBeChecked();
    });
});
