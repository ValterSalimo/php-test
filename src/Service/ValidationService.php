<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Service for data validation
 */
class ValidationService
{
    /**
     * Validate recipe data
     *
     * @throws ValidationException
     */
    public function validateRecipe(array $data, bool $isUpdate = false): array
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Recipe name cannot be empty']),
                new Assert\Length(['min' => 3, 'max' => 255])
            ],
            'prepTime' => [
                new Assert\NotBlank(['message' => 'Preparation time cannot be empty']),
                new Assert\Type(['type' => 'integer']),
                new Assert\Range(['min' => 1, 'max' => 1440])
            ],
            'difficulty' => [
                new Assert\NotBlank(['message' => 'Difficulty cannot be empty']),
                new Assert\Type(['type' => 'integer']),
                new Assert\Range(['min' => 1, 'max' => 3])
            ],
            'vegetarian' => [
                new Assert\Type(['type' => 'boolean']),
            ]
        ]);

        // In update mode, fields are optional
        if ($isUpdate) {
            $constraints = new Assert\Collection([
                'name' => new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 3, 'max' => 255])
                ]),
                'prepTime' => new Assert\Optional([
                    new Assert\Type(['type' => 'integer']),
                    new Assert\Range(['min' => 1, 'max' => 1440])
                ]),
                'difficulty' => new Assert\Optional([
                    new Assert\Type(['type' => 'integer']),
                    new Assert\Range(['min' => 1, 'max' => 3])
                ]),
                'vegetarian' => new Assert\Optional([
                    new Assert\Type(['type' => 'boolean'])
                ])
            ]);
        }

        $violations = $validator->validate($data, $constraints);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                $errors[$propertyPath] = $violation->getMessage();
            }
            
            throw new ValidationException('Validation failed', $errors);
        }
        
        return $data;
    }

    /**
     * Validate rating data
     *
     * @throws ValidationException
     */
    public function validateRating(array $data): int
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'rating' => [
                new Assert\NotBlank(['message' => 'Rating cannot be empty']),
                new Assert\Type(['type' => 'integer']),
                new Assert\Range(['min' => 1, 'max' => 5])
            ]
        ]);

        $violations = $validator->validate($data, $constraints);
        
        if (count($violations) > 0 || !isset($data['rating'])) {
            throw new ValidationException('Rating must be between 1 and 5');
        }
        
        return (int)$data['rating'];
    }

    /**
     * Validate authentication data
     *
     * @throws ValidationException
     */
    public function validateAuth(array $data): array
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'username' => [
                new Assert\NotBlank(['message' => 'Username cannot be empty']),
                new Assert\Length(['min' => 3, 'max' => 50]),
                new Assert\Regex(['pattern' => '/^[a-zA-Z0-9_]+$/', 'message' => 'Username can only contain letters, numbers and underscore'])
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Password cannot be empty']),
                new Assert\Length(['min' => 6, 'max' => 255])
            ]
        ]);

        $violations = $validator->validate($data, $constraints);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                $errors[$propertyPath] = $violation->getMessage();
            }
            
            throw new ValidationException('Validation failed', $errors);
        }
        
        return $data;
    }
}
