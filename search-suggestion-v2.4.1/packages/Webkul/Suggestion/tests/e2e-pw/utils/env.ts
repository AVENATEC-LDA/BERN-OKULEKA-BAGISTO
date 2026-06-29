import dotenv from "dotenv";
import path from "path";

dotenv.config({ path: path.resolve(__dirname, "../.env") });

function required(name: string): string {
    const value = process.env[name];

    if (!value || !value.trim()) {
        throw new Error(
            `[e2e-pw] Missing required environment variable: ${name}. ` +
                `Copy .env.example to .env and set ${name}, or export it in your shell.`,
        );
    }

    return value.trim();
}

function optional(name: string, defaultValue: string): string {
    const value = process.env[name];

    return value && value.trim() ? value.trim() : defaultValue;
}

function optionalNumber(name: string, defaultValue: number): number {
    const raw = process.env[name];

    if (raw == null || !raw.trim()) {
        return defaultValue;
    }

    const parsed = Number(raw);

    if (!Number.isFinite(parsed)) {
        throw new Error(
            `[e2e-pw] Environment variable ${name} must be a finite number, got "${raw}".`,
        );
    }

    return parsed;
}

function optionalBoolean(name: string, defaultValue: boolean): boolean {
    const raw = process.env[name];

    if (raw == null || !raw.trim()) {
        return defaultValue;
    }

    return ["1", "true", "yes", "on"].includes(raw.trim().toLowerCase());
}

function stripTrailingSlashes(url: string): string {
    return url.replace(/\/+$/, "");
}

export const env = {
    baseUrl: stripTrailingSlashes(required("BAGISTO_BASE_URL")),

    adminEmail: optional("BAGISTO_ADMIN_EMAIL", "admin@example.com"),
    adminPassword: optional("BAGISTO_ADMIN_PASSWORD", "admin123"),

    actionTimeout: optionalNumber("ACTION_TIMEOUT", 10_000),
    navigationTimeout: optionalNumber("NAVIGATION_TIMEOUT", 15_000),

    headed: optionalBoolean("HEADED", false),

    bagistoVersion: optional("BAGISTO_VERSION", "2.4"),
} as const;

export type Env = typeof env;
