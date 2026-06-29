import type { Locator, Page, Response } from "@playwright/test";
import { BasePage } from "../../../BasePage";

export type SuggestionConfig = {
    status: boolean;
    minSearchTerms: number;
    productsLimit: number;
    showSearchedTerms: boolean;
    showCategories: boolean;
};

export class SuggestionConfigPage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async open(): Promise<Response | null> {
        return this.visit("admin/configuration/suggestion/suggestion");
    }

    private statusToggleInput(): Locator {
        return this.page.locator(
            'input[type="checkbox"][name="suggestion[suggestion][general][status]"]',
        );
    }

    private minSearchTermsInput(): Locator {
        return this.page.locator(
            'input[name="suggestion[suggestion][general][min_search_terms]"]',
        );
    }

    private productsLimitInput(): Locator {
        return this.page.locator(
            'input[name="suggestion[suggestion][general][products_limit]"]',
        );
    }

    private showSearchedTermsToggleInput(): Locator {
        return this.page.locator(
            'input[type="checkbox"][name="suggestion[suggestion][general][show_searched_terms]"]',
        );
    }

    private showCategoriesToggleInput(): Locator {
        return this.page.locator(
            'input[type="checkbox"][name="suggestion[suggestion][general][show_categories]"]',
        );
    }

    private saveButton(): Locator {
        return this.page.getByRole("button", { name: "Save Configuration" });
    }

    private successFlash(): Locator {
        return this.page.getByText("Configuration saved successfully").first();
    }

    async applyConfiguration(config: SuggestionConfig): Promise<void> {
        await this.setToggle(this.statusToggleInput(), config.status);
        await this.minSearchTermsInput().fill(String(config.minSearchTerms));
        await this.productsLimitInput().fill(String(config.productsLimit));
        await this.setToggle(
            this.showSearchedTermsToggleInput(),
            config.showSearchedTerms,
        );
        await this.setToggle(
            this.showCategoriesToggleInput(),
            config.showCategories,
        );

        await this.saveButton().click();
        await this.successFlash().waitFor({ state: "visible" });
    }

    async setStatus(enabled: boolean): Promise<void> {
        await this.setToggle(this.statusToggleInput(), enabled);
        await this.saveButton().click();
        await this.successFlash().waitFor({ state: "visible" });
    }

    private async setToggle(
        toggleInput: Locator,
        desired: boolean,
    ): Promise<void> {
        const isChecked = await toggleInput.isChecked();

        if (isChecked === desired) {
            return;
        }

        const labelFor = await toggleInput.getAttribute("id");

        if (labelFor) {
            await this.page.locator(`label[for="${labelFor}"]`).click();

            return;
        }

        await toggleInput.click();
    }
}
