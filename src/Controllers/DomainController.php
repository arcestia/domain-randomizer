<?php
/**
 * Domain Controller
 * Created: 2024-12-17
 * Author: Laurensius Jeffrey
 * License: MIT
 */

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;

class DomainController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function generateRandomString($length = 30)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    // Source Domains API
    public function listSources(Request $request, Response $response): Response
    {
        try {
            $stmt = $this->db->query('SELECT * FROM source_domains ORDER BY domain');
            $sources = $stmt->fetchAll();
            return $this->jsonResponse($response, $sources);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function addSource(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['domain'])) {
                return $this->jsonResponse($response, ['error' => 'Domain is required'], 400);
            }

            $stmt = $this->db->prepare('INSERT INTO source_domains (domain) VALUES (?)');
            $stmt->execute([$data['domain']]);
            
            return $this->jsonResponse($response, [
                'id' => $this->db->lastInsertId(),
                'domain' => $data['domain']
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteSource(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];

            // Check for existing rules
            $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM domain_rules WHERE source_domain_id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                return $this->jsonResponse($response, [
                    'error' => 'Cannot delete source domain that is being used in rules. Delete associated rules first.'
                ], 400);
            }

            $stmt = $this->db->prepare('DELETE FROM source_domains WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                return $this->jsonResponse($response, ['error' => 'Source domain not found'], 404);
            }

            return $this->jsonResponse($response, ['message' => 'Source domain deleted successfully']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    // Target Domains API
    public function listTargets(Request $request, Response $response): Response
    {
        try {
            $stmt = $this->db->query('SELECT * FROM target_domains ORDER BY domain');
            $targets = $stmt->fetchAll();
            return $this->jsonResponse($response, $targets);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function addTarget(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['domain'])) {
                return $this->jsonResponse($response, ['error' => 'Domain is required'], 400);
            }

            $stmt = $this->db->prepare('INSERT INTO target_domains (domain) VALUES (?)');
            $stmt->execute([$data['domain']]);
            
            return $this->jsonResponse($response, [
                'id' => $this->db->lastInsertId(),
                'domain' => $data['domain']
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteTarget(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];

            // Check for existing rules
            $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM domain_rules WHERE target_domain_id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                return $this->jsonResponse($response, [
                    'error' => 'Cannot delete target domain that is being used in rules. Delete associated rules first.'
                ], 400);
            }

            $stmt = $this->db->prepare('DELETE FROM target_domains WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                return $this->jsonResponse($response, ['error' => 'Target domain not found'], 404);
            }

            return $this->jsonResponse($response, ['message' => 'Target domain deleted successfully']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    // Rules API
    public function listRules(Request $request, Response $response): Response
    {
        try {
            $stmt = $this->db->query('
                SELECT 
                    dr.id,
                    sd.domain as source_domain,
                    td.domain as target_domain,
                    dr.active
                FROM domain_rules dr
                JOIN source_domains sd ON sd.id = dr.source_domain_id
                JOIN target_domains td ON td.id = dr.target_domain_id
                ORDER BY sd.domain, td.domain
            ');
            $rules = $stmt->fetchAll();
            return $this->jsonResponse($response, $rules);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function addRule(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['source_domain']) || empty($data['target_domain'])) {
                return $this->jsonResponse($response, ['error' => 'Source and target domains are required'], 400);
            }

            // Get domain IDs
            $stmt = $this->db->prepare('SELECT id FROM source_domains WHERE domain = ?');
            $stmt->execute([$data['source_domain']]);
            $source = $stmt->fetch();

            $stmt = $this->db->prepare('SELECT id FROM target_domains WHERE domain = ?');
            $stmt->execute([$data['target_domain']]);
            $target = $stmt->fetch();

            if (!$source || !$target) {
                return $this->jsonResponse($response, ['error' => 'Source or target domain not found'], 404);
            }

            $stmt = $this->db->prepare('INSERT INTO domain_rules (source_domain_id, target_domain_id) VALUES (?, ?)');
            $stmt->execute([$source['id'], $target['id']]);

            return $this->jsonResponse($response, [
                'id' => $this->db->lastInsertId(),
                'source_domain' => $data['source_domain'],
                'target_domain' => $data['target_domain']
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function updateRule(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $data = $request->getParsedBody();

            if (!isset($data['active'])) {
                return $this->jsonResponse($response, ['error' => 'Active status is required'], 400);
            }

            $active = $data['active'] ? 1 : 0;
            $stmt = $this->db->prepare('UPDATE domain_rules SET active = ? WHERE id = ?');
            $stmt->execute([$active, $id]);

            if ($stmt->rowCount() === 0) {
                return $this->jsonResponse($response, ['error' => 'Rule not found'], 404);
            }

            return $this->jsonResponse($response, [
                'id' => (int)$id,
                'active' => (bool)$active
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteRule(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $stmt = $this->db->prepare('DELETE FROM domain_rules WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                return $this->jsonResponse($response, ['error' => 'Rule not found'], 404);
            }

            return $this->jsonResponse($response, ['message' => 'Rule deleted successfully']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    public function handleRedirect(Request $request, Response $response): Response
    {
        try {
            $hostname = $request->getUri()->getHost();

            $stmt = $this->db->prepare("
                SELECT td.domain 
                FROM target_domains td
                JOIN domain_rules dr ON dr.target_domain_id = td.id
                JOIN source_domains sd ON sd.id = dr.source_domain_id
                WHERE sd.domain = ? 
                AND sd.active = 1 
                AND td.active = 1 
                AND dr.active = 1
            ");
            $stmt->execute([$hostname]);
            $targetDomains = $stmt->fetchAll();

            if (empty($targetDomains)) {
                throw new \Exception("No active target domains configured for {$hostname}");
            }

            $randomDomain = $targetDomains[array_rand($targetDomains)]['domain'];
            $randomSubdomain = $this->generateRandomString();
            $redirectUrl = "https://{$randomSubdomain}.{$randomDomain}";

            // Log redirect
            $stmt = $this->db->prepare(
                'INSERT INTO redirects (source_domain, target_url, created_at) VALUES (?, ?, NOW())'
            );
            $stmt->execute([$hostname, $redirectUrl]);

            return $response
                ->withHeader('Location', $redirectUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }
}
