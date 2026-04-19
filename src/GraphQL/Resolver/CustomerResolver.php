<?php

namespace App\GraphQL\Resolver;

use App\Entity\Customer;
use App\Repository\CustomerRepository;

class CustomerResolver
{
    private $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function resolveCustomers(): array
    {
        return $this->customerRepository->findAll();
    }

    public function resolveCustomer(array $args): ?Customer
    {
        return $this->customerRepository->find($args['id']);
    }
}
