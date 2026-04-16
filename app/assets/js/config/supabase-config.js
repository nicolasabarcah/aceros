export const ACEROS_SUPABASE_URL = "https://qklorlniovbkphoflzfw.supabase.co";
export const ACEROS_SUPABASE_ANON_KEY = "sb_publishable_GNeBJ_imwr5pCm7WJfr3_w_fUrGffiJ";

export function getSupabaseConfig() {
    const globalConfig = window.ACEROS_SUPABASE_CONFIG || {};
    const url = globalConfig.url || window.ACEROS_SUPABASE_URL || ACEROS_SUPABASE_URL;
    const anonKey = globalConfig.anonKey || window.ACEROS_SUPABASE_ANON_KEY || ACEROS_SUPABASE_ANON_KEY;

    if (!url || !anonKey) {
        throw new Error("Falta configurar Supabase. Completa assets/js/config/supabase-config.js con la URL del proyecto y la anon key.");
    }

    return {
        url,
        anonKey
    };
}