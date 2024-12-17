/**
 * Domain Randomizer
 * Created: 2024-12-17
 * Author: Laurensius Jeffrey
 * License: MIT
 */

const express = require('express');
const bodyParser = require('body-parser');
const db = require('./db');
const apiRoutes = require('./api');
require('dotenv').config();

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(bodyParser.json());

// API Routes
app.use('/api', apiRoutes);

function generateRandomString(length) {
    const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
}

app.get('/', async (req, res) => {
    const conn = await db.getConnection();
    try {
        // Get hostname that was used to access this server
        const hostname = req.hostname;

        // Get allowed target domains for this source domain
        const query = `
            SELECT td.domain 
            FROM target_domains td
            JOIN domain_rules dr ON dr.target_domain_id = td.id
            JOIN source_domains sd ON sd.id = dr.source_domain_id
            WHERE sd.domain = ? 
            AND sd.active = 1 
            AND td.active = 1 
            AND dr.active = 1
        `;
        
        const targetDomains = await conn.query(query, [hostname]);
        
        if (targetDomains.length === 0) {
            throw new Error(`No active target domains configured for ${hostname}`);
        }

        // Select random domain from allowed targets
        const randomDomain = targetDomains[Math.floor(Math.random() * targetDomains.length)].domain;
        
        // Generate random subdomain
        const randomSubdomain = generateRandomString(30);
        
        // Create full redirect URL
        const redirectUrl = `https://${randomSubdomain}.${randomDomain}`;
        
        // Log the redirect
        await conn.query(
            'INSERT INTO redirects (source_domain, target_url, created_at) VALUES (?, ?, NOW())',
            [hostname, redirectUrl]
        );

        // Perform redirect
        res.redirect(redirectUrl);
    } catch (err) {
        console.error('Error:', err);
        res.status(500).send('Internal Server Error');
    } finally {
        if (conn) conn.release();
    }
});

app.listen(port, () => {
    console.log(`Domain randomizer listening at http://localhost:${port}`);
});
