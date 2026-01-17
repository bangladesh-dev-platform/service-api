import pg from 'pg';
import { dbConfig } from '../../config/database.config.js';

const { Pool } = pg;

// Create a singleton pool
const pool = new Pool(dbConfig);

pool.on('error', (err) => {
    console.error('Unexpected error on idle client', err);
});

export const query = (text: string, params?: any[]) => pool.query(text, params);

export const getClient = () => pool.connect();

export const connectDatabase = async () => {
    const client = await pool.connect();
    try {
        await client.query('SELECT 1');
        return true;
    } finally {
        client.release();
    }
};

export default {
    query,
    getClient,
    connectDatabase,
    pool
};
