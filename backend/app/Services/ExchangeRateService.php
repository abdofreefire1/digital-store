<?php
namespace App\Services;

class ExchangeRateService {
    
    /**
     * Get the current DZD to USDT exchange rate.
     * Caching logic can be implemented here if fetching from an external API.
     */
    public function getDzdToUsdtRate(): float {
        $source = USDT_RATE_SOURCE; // 'manual' or 'api'
        
        if ($source === 'manual') {
            $baseRate = (float) MANUAL_USDT_RATE;
        } else {
            // TODO: Implement actual API fetch here (e.g., Binance P2P API)
            // For now, fallback to manual if API is requested but not implemented
            $baseRate = (float) MANUAL_USDT_RATE; 
        }

        // Add markup if configured (e.g., to cover transaction fees)
        $markupPercent = defined('RATE_MARKUP_PERCENT') ? (float) RATE_MARKUP_PERCENT : 0;
        $fixedMargin = defined('RATE_FIXED_MARGIN') ? (float) RATE_FIXED_MARGIN : 0;

        $finalRate = $baseRate + ($baseRate * ($markupPercent / 100)) + $fixedMargin;

        return $finalRate;
    }

    /**
     * Convert DZD to USDT
     */
    public function convertDzdToUsdt(float $amountDzd): float {
        $rate = $this->getDzdToUsdtRate();
        if ($rate <= 0) return 0;
        return round($amountDzd / $rate, 4);
    }
}
