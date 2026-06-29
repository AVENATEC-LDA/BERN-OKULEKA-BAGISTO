import type { Page, Response } from "@playwright/test";
import { BasePage } from "../BasePage";

export class AdminLoginPage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    async open(): Promise<Response | null> {
        return this.visit("admin/login");
    }

    async submitCredentials(email: string, password: string): Promise<void> {
        await this.page.fill('input[name="email"]', email);
        await this.page.fill('input[name="password"]', password);
        await this.page.press('input[name="password"]', "Enter");
    }
}
