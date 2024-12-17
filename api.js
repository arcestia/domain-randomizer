/**
 * Domain Randomizer API Routes
 * Created: 2024-12-17
 * Author: Laurensius Jeffrey
 * 
 * PROPRIETARY AND CONFIDENTIAL
 * For internal development use only
 * Unauthorized copying or distribution is strictly prohibited
 */

const express = require('express');
const router = express.Router();
const db = require('./db');

// Get all source domains
router.get('/sources', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const sources = await conn.query('SELECT * FROM source_domains ORDER BY domain');
        res.json(sources);
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Get all target domains
router.get('/targets', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const targets = await conn.query('SELECT * FROM target_domains ORDER BY domain');
        res.json(targets);
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Get rules
router.get('/rules', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const rules = await conn.query(`
            SELECT 
                dr.id,
                sd.domain as source_domain,
                td.domain as target_domain,
                dr.active
            FROM domain_rules dr
            JOIN source_domains sd ON sd.id = dr.source_domain_id
            JOIN target_domains td ON td.id = dr.target_domain_id
            ORDER BY sd.domain, td.domain
        `);
        res.json(rules);
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Add source domain
router.post('/sources', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { domain } = req.body;
        if (!domain) {
            return res.status(400).json({ error: 'Domain is required' });
        }
        const result = await conn.query(
            'INSERT INTO source_domains (domain) VALUES (?)',
            [domain]
        );
        res.json({ id: result.insertId, domain });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Add target domain
router.post('/targets', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { domain } = req.body;
        if (!domain) {
            return res.status(400).json({ error: 'Domain is required' });
        }
        const result = await conn.query(
            'INSERT INTO target_domains (domain) VALUES (?)',
            [domain]
        );
        res.json({ id: result.insertId, domain });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Add rule
router.post('/rules', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { source_domain, target_domain } = req.body;
        if (!source_domain || !target_domain) {
            return res.status(400).json({ error: 'Source and target domains are required' });
        }

        // Get domain IDs
        const sourceResult = await conn.query('SELECT id FROM source_domains WHERE domain = ?', [source_domain]);
        const targetResult = await conn.query('SELECT id FROM target_domains WHERE domain = ?', [target_domain]);

        if (sourceResult.length === 0 || targetResult.length === 0) {
            return res.status(404).json({ error: 'Source or target domain not found' });
        }

        const result = await conn.query(
            'INSERT INTO domain_rules (source_domain_id, target_domain_id) VALUES (?, ?)',
            [sourceResult[0].id, targetResult[0].id]
        );
        res.json({ id: result.insertId, source_domain, target_domain });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Toggle rule status
router.patch('/rules/:id', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { id } = req.params;
        const { active } = req.body;
        
        if (typeof active !== 'boolean') {
            return res.status(400).json({ error: 'Active status must be boolean' });
        }

        await conn.query(
            'UPDATE domain_rules SET active = ? WHERE id = ?',
            [active, id]
        );
        res.json({ id, active });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Delete rule
router.delete('/rules/:id', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { id } = req.params;
        await conn.query('DELETE FROM domain_rules WHERE id = ?', [id]);
        res.json({ message: 'Rule deleted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Delete source domain
router.delete('/sources/:id', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { id } = req.params;
        
        // First check if there are any rules using this source domain
        const rules = await conn.query(
            'SELECT COUNT(*) as count FROM domain_rules WHERE source_domain_id = ?',
            [id]
        );

        if (rules[0].count > 0) {
            return res.status(400).json({ 
                error: 'Cannot delete source domain that is being used in rules. Delete associated rules first.' 
            });
        }

        // Delete the source domain
        const result = await conn.query('DELETE FROM source_domains WHERE id = ?', [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({ error: 'Source domain not found' });
        }

        res.json({ message: 'Source domain deleted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

// Delete target domain
router.delete('/targets/:id', async (req, res) => {
    const conn = await db.getConnection();
    try {
        const { id } = req.params;
        
        // First check if there are any rules using this target domain
        const rules = await conn.query(
            'SELECT COUNT(*) as count FROM domain_rules WHERE target_domain_id = ?',
            [id]
        );

        if (rules[0].count > 0) {
            return res.status(400).json({ 
                error: 'Cannot delete target domain that is being used in rules. Delete associated rules first.' 
            });
        }

        // Delete the target domain
        const result = await conn.query('DELETE FROM target_domains WHERE id = ?', [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({ error: 'Target domain not found' });
        }

        res.json({ message: 'Target domain deleted successfully' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    } finally {
        if (conn) conn.release();
    }
});

module.exports = router;
