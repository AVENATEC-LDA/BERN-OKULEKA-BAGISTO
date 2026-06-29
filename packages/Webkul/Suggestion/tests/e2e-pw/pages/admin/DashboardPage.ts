import type { Page, Response } from "@playwright/test";
import { BasePage } from "../BasePage";

export class AdminDashboardPage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async open(): Promise<Response | null> {
        return this.visit("admin/dashboard");
    }

    async waitUntilLoaded(): Promise<void> {
        await this.page.waitForURL("**/admin/dashboard");
    }
}
