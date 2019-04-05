<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Eccube\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * PaymentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PaymentRepository extends EntityRepository
{

    public function findOrCreate($id)
    {
        if ($id == 0) {

            $Payment = $this->findOneBy(array(), array('rank' => 'DESC'));

            $rank = 1;
            if ($Payment) {
                $rank = $Payment->getRank() + 1;
            }

            $Payment = new \Eccube\Entity\Payment();
            $Payment
                ->setRank($rank)
                ->setDelFlg(0)
                ->setFixFlg(1)
                ->setChargeFlg(1);
        } else {
            $Payment = $this->find($id);
        }

        return $Payment;
    }

    public function findAllArray()
    {

        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT p FROM Eccube\Entity\Payment p INDEX BY p.id');
        $result = $query
            ->getResult(Query::HYDRATE_ARRAY);

        return $result;
    }

    /**
     * 支払方法を取得
     * 条件によってはDoctrineのキャッシュが返されるため、arrayで結果を返すパターンも用意
     *
     * @param $delivery
     * @param $returnType true : Object、false: arrayが戻り値
     * @return array
     */
    public function findPayments($delivery, $returnType = false)
    {

        $query = $this->createQueryBuilder('p')
            ->innerJoin('Eccube\Entity\PaymentOption', 'po', 'WITH', 'po.payment_id = p.id')
            ->where('po.Delivery = (:delivery)')
            ->orderBy('p.rank', 'DESC')
            ->setParameter('delivery', $delivery)
            ->getQuery();

        $query->expireResultCache(false);

        if ($returnType) {
            $payments = $query->getResult();
        } else {
            $payments = $query->getArrayResult();
        }

        return $payments;
    }

    /**
     * 共通の支払方法を取得
     *
     * @param $deliveries
     * @return array
     */
    public function findAllowedPayments($deliveries)
    {
        $payments = array();
        $productTypes = array();

        foreach ($deliveries as $Delivery) {
            $p = $this->findPayments($Delivery);
            if ($p == null) {
                continue;
            }
            foreach ($p as $payment) {
                $payments[$payment['id']] = $payment;
                $productTypes[$Delivery->getProductType()->getId()][$payment['id']] = true;
            }
        }
        foreach($payments as $key => $payment){
            foreach($productTypes as $row){
                if(!isset($row[$payment['id']])){
                    unset($payments[$key]);
                    continue;
                }
            }
        }
        return $payments;
    }
}
