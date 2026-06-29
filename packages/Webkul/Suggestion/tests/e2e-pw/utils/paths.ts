import fs from "fs";
import path from "path";

export const E2E_ROOT_PATH = path.resolve(__dirname, "..");
export const DATA_PATH = path.join(E2E_ROOT_PATH, "data");
export const STATE_DIR_PATH = path.join(E2E_ROOT_PATH, ".state");
export const ADMIN_AUTH_STATE_PATH = path.join(
    STATE_DIR_PATH,
    "admin-auth.json",
);

export function ensureStateDir(): void {
    fs.mkdirSync(STATE_DIR_PATH, { recursive: true });
}
