<?php
// ============================================
// PREDICTION ENGINE — The "AI" Brain
// ============================================

require_once __DIR__ . '/../db.php';

class PredictEngine {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Generate predictions for all products
     */
    public function generateAllPredictions() {
        $products = $this->conn->query("SELECT * FROM products");
        $results = [];

        while ($product = $products->fetch_assoc()) {
            $results[] = $this->predictForProduct($product);
        }

        return $results;
    }

    /**
     * Generate prediction for a single product
     */
    public function predictForProduct($product) {
        $pid = $product['id'];

        // Get stock-out movements for last 30 days
        $stmt = $this->conn->prepare("
            SELECT SUM(quantity) as total_out, COUNT(*) as num_transactions
            FROM stock_movements
            WHERE product_id = ? AND type = 'out'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $last30 = $stmt->get_result()->fetch_assoc();

        // Get stock-out for last 7 days (for trend detection)
        $stmt2 = $this->conn->prepare("
            SELECT SUM(quantity) as total_out
            FROM stock_movements
            WHERE product_id = ? AND type = 'out'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt2->bind_param("i", $pid);
        $stmt2->execute();
        $last7 = $stmt2->get_result()->fetch_assoc();

        // Get stock-out for previous 7 days (days 8-14 ago)
        $stmt3 = $this->conn->prepare("
            SELECT SUM(quantity) as total_out
            FROM stock_movements
            WHERE product_id = ? AND type = 'out'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt3->bind_param("i", $pid);
        $stmt3->execute();
        $prev7 = $stmt3->get_result()->fetch_assoc();

        // Calculate average daily consumption
        $total_out_30 = (float)($last30['total_out'] ?? 0);
        $avg_daily = $total_out_30 / 30;

        // Calculate days until stockout
        $current_stock = (int)$product['current_stock'];
        $days_until_stockout = ($avg_daily > 0) ? floor($current_stock / $avg_daily) : 999;

        // Calculate predicted stockout date
        $stockout_date = date('Y-m-d', strtotime("+$days_until_stockout days"));

        // Trend detection
        $this_week = (float)($last7['total_out'] ?? 0);
        $prev_week = (float)($prev7['total_out'] ?? 0);

        $trend = 'stable';
        if ($prev_week > 0) {
            $change_pct = (($this_week - $prev_week) / $prev_week) * 100;
            if ($change_pct > 20) $trend = 'increasing';
            elseif ($change_pct < -20) $trend = 'decreasing';
        }

        // Suggested order quantity
        $lead_time = (int)$product['lead_time_days'];
        $safety_buffer = 7; // days
        $suggested_qty = ceil($avg_daily * ($lead_time + $safety_buffer));

        // Adjust for trend
        if ($trend === 'increasing') {
            $suggested_qty = ceil($suggested_qty * 1.25);
        } elseif ($trend === 'decreasing') {
            $suggested_qty = ceil($suggested_qty * 0.85);
        }

        // Determine stock status
        $status = 'healthy'; // green
        if ($days_until_stockout <= 7) $status = 'critical'; // red
        elseif ($days_until_stockout <= 30) $status = 'warning'; // yellow

        // Save prediction to database
        $this->savePrediction($pid, $avg_daily, $days_until_stockout, $stockout_date, $suggested_qty, $trend);

        return [
            'product' => $product,
            'avg_daily_consumption' => round($avg_daily, 2),
            'days_until_stockout' => $days_until_stockout,
            'predicted_stockout_date' => $stockout_date,
            'suggested_order_qty' => $suggested_qty,
            'trend' => $trend,
            'status' => $status,
            'this_week_usage' => $this_week,
            'prev_week_usage' => $prev_week
        ];
    }

    /**
     * Save prediction to database
     */
    private function savePrediction($pid, $avg_daily, $days_stockout, $stockout_date, $order_qty, $trend) {
        // Remove old prediction for this product
        $stmt = $this->conn->prepare("DELETE FROM predictions WHERE product_id = ?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();

        // Insert new
        $stmt = $this->conn->prepare("
            INSERT INTO predictions (product_id, avg_daily_consumption, days_until_stockout, predicted_stockout_date, suggested_order_qty, trend)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("idisss", $pid, $avg_daily, $days_stockout, $stockout_date, $order_qty, $trend);
        $stmt->execute();
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats() {
        $predictions = $this->generateAllPredictions();

        $total = count($predictions);
        $critical = 0;
        $warning = 0;
        $healthy = 0;
        $total_value = 0;

        foreach ($predictions as $p) {
            if ($p['status'] === 'critical') $critical++;
            elseif ($p['status'] === 'warning') $warning++;
            else $healthy++;
            $total_value += $p['product']['current_stock'] * $p['product']['unit_price'];
        }

        return [
            'total_products' => $total,
            'critical' => $critical,
            'warning' => $warning,
            'healthy' => $healthy,
            'total_inventory_value' => round($total_value, 2),
            'predictions' => $predictions
        ];
    }

    /**
     * Get daily consumption history for a product (for charts)
     */
    public function getDailyHistory($product_id, $days = 14) {
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, SUM(quantity) as total
            FROM stock_movements
            WHERE product_id = ? AND type = 'out'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->bind_param("ii", $product_id, $days);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    }
}
?>
