import { createClient } from "https://esm.sh/@supabase/supabase-js@2";
import { getSupabaseConfig } from "../config/supabase-config.js";

let supabaseClient = null;

function getClient() {
    if (!supabaseClient) {
        const config = getSupabaseConfig();
        supabaseClient = createClient(config.url, config.anonKey);
    }

    return supabaseClient;
}

async function readData(query, fallbackMessage) {
    const { data, error } = await query;
    if (error) {
        throw new Error(error.message || fallbackMessage);
    }
    return data || [];
}

function normalizeAccount(row) {
    return {
        id: Number(row.id),
        nombre: row.nombre || "",
        saldo_inicial: Number(row.saldo_inicial || 0),
        activa: Boolean(row.activa),
        orden: row.orden === null || row.orden === undefined ? null : Number(row.orden)
    };
}

function normalizeCategory(row) {
    return {
        id: Number(row.id),
        nombre: row.nombre || "",
        ingreso: Boolean(row.ingreso),
        archivada: Boolean(row.archivada)
    };
}

function normalizeTransaction(row) {
    return {
        id: Number(row.id),
        fecha: row.fecha || "",
        concepto: row.concepto || "",
        monto: Number(row.monto || 0),
        descripcion: row.descripcion || "",
        cuenta_id: Number(row.cuenta_id),
        categoria_id: Number(row.categoria_id),
        created_at: row.created_at || null,
        cuenta: row.cuenta ? normalizeAccount(row.cuenta) : null,
        categoria: row.categoria ? normalizeCategory(row.categoria) : null
    };
}

export async function listAccounts() {
    const rows = await readData(
        getClient()
            .from("cuentas")
            .select("id,nombre,saldo_inicial,activa,orden")
            .eq("activa", true)
            .order("orden", { ascending: true, nullsFirst: false })
            .order("nombre", { ascending: true }),
        "No se pudieron cargar las cuentas."
    );

    return rows.map(normalizeAccount);
}

export async function getAccountById(accountId) {
    const rows = await readData(
        getClient()
            .from("cuentas")
            .select("id,nombre,saldo_inicial,activa,orden")
            .eq("id", Number(accountId))
            .eq("activa", true)
            .limit(1),
        "No se pudo cargar la cuenta."
    );

    return rows.length > 0 ? normalizeAccount(rows[0]) : null;
}

export async function listCategories(options) {
    const settings = options || {};
    let query = getClient()
        .from("categorias")
        .select("id,nombre,ingreso,archivada")
        .eq("archivada", false)
        .order("nombre", { ascending: true });

    if (settings.type === "ingreso") {
        query = query.eq("ingreso", true);
    }

    if (settings.type === "egreso") {
        query = query.eq("ingreso", false);
    }

    const rows = await readData(query, "No se pudieron cargar las categorías.");
    return rows.map(normalizeCategory);
}

export async function listTransactions(options) {
    const settings = options || {};
    let query = getClient()
        .from("cuentas_registros")
        .select("id,fecha,concepto,monto,descripcion,cuenta_id,categoria_id,created_at,cuenta:cuenta_id(id,nombre,saldo_inicial,activa,orden),categoria:categoria_id(id,nombre,ingreso,archivada)")
        .order("fecha", { ascending: false })
        .order("id", { ascending: false });

    if (settings.accountId && Number(settings.accountId) > 0) {
        query = query.eq("cuenta_id", Number(settings.accountId));
    }

    const rows = await readData(query, "No se pudieron cargar las transacciones.");
    return rows.map(normalizeTransaction);
}

export async function createTransaction(payload) {
    const body = {
        fecha: payload.fecha,
        concepto: String(payload.concepto || "").trim(),
        cuenta_id: Number(payload.cuentaId),
        categoria_id: Number(payload.categoriaId),
        monto: Math.abs(Number(payload.monto || 0)),
        descripcion: String(payload.descripcion || "").trim() || null
    };

    const { data, error } = await getClient()
        .from("cuentas_registros")
        .insert(body)
        .select("id")
        .single();

    if (error) {
        throw new Error(error.message || "No se pudo guardar la transacción.");
    }

    return data;
}