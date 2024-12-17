/**
 * Domain Randomizer Database Configuration
 * Created: 2024-12-17
 * Author: Laurensius Jeffrey
 * 
 * PROPRIETARY AND CONFIDENTIAL
 * For internal development use only
 * Unauthorized copying or distribution is strictly prohibited
 */

const mariadb = require('mariadb');
require('dotenv').config();

const pool = mariadb.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    port: process.env.DB_PORT,
    connectionLimit: 5
});

module.exports = {
    getConnection: async () => {
        return await pool.getConnection();
    }
};
