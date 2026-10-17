# PHP Developer Technical Assessment  
## Rick and Morty Encyclopedia

## Overview

This exercise is intended to evaluate your ability to design and build a **small production-quality web application using modern PHP practices**.

You will create a web-based encyclopedia using data from the Rick and Morty API.

The emphasis of this assessment is on:

- **Clean architecture and maintainable code**
- **Proper MVC separation**
- **Object-oriented PHP**
- API integration
- Security and resilience
- Performance and usability
- Thoughtful engineering decisions

Visual design is considered a bonus, but **functionality, code quality, and implementation approach are the primary evaluation criteria**.

---

# Technical Requirements

Your solution **must** use:

- **PHP 8.3 or higher**
- Composer
- Git

You may use any PHP framework, front-end framework, CSS library, or Composer packages you feel are appropriate.

However:

- **All communication with the Rick and Morty API must be implemented using your own application code**
- You may use generic HTTP clients/libraries (such as Guzzle, Symfony HTTP Client, cURL wrappers, etc.)
- **You must not use SDKs, wrappers, or dedicated libraries built specifically for the Rick and Morty API**

---

# Application Requirements

Your application **must follow a clear MVC structure** and use **object-oriented PHP throughout**.

The application should allow users to:

## Character Listing

Display a browsable list of Rick and Morty characters with:

- **Pagination**
- **Search functionality**
- **Filtering functionality**

The browsing experience should remain responsive and user-friendly when navigating larger datasets.

---

## Character Details

Display detailed information for an individual character, including at minimum:

- At least one image
- Name
- Species
- Origin
- Episodes in which the character appears

---

# API

Use the public Rick and Morty REST API:

Rick and Morty API: https://rickandmortyapi.com

Authentication is not required.

Please note that the API is rate-limited, and your implementation should account for this appropriately where reasonable.

---

# Production Readiness

Your submission should be considered **production-ready**.

Please consider:

- Input validation and sanitization
- Error handling
- Secure coding practices
- Protection against common attack vectors
- Sensible application structure
- Performance considerations
- Maintainability and readability
- Dependency management
- Environment configuration

You are encouraged to document **architectural decisions, trade-offs, and notable implementation choices** made during development.

---

# Candidate Authorship & Use of AI Tools

This assessment is intended to evaluate **your own engineering ability and decision-making**.

You may use developer assistance tools (including AI tools, documentation resources, code completion, and reference materials) as part of your workflow. However:

- **The architecture, implementation, and final submission must be your own work**
- **You must fully understand, review, and be able to explain all submitted code**
- **Blindly generated, copy-pasted, or unreviewed AI output is not acceptable**
- We may discuss implementation decisions, architecture, and specific areas of your code during follow-up conversations

The goal is not to prohibit modern tooling, but to assess your **practical software engineering skills, judgment, and ownership of the submitted solution**.

---

# Time Expectation

This assessment is intentionally scoped to be completed within approximately **2–3 hours**.

We are not expecting a fully featured enterprise platform. We are more interested in seeing your:

- Engineering approach
- Prioritization
- Implementation quality
- Understanding of the codebase

…than an overly polished feature set.

---

# Submission

To submit your solution:

1. Fork this repository
2. **Commit your work regularly as you progress**
3. Submit a link to your completed fork

Your application should be runnable locally from the repository without requiring additional setup beyond standard project installation steps.

Please include **clear setup instructions** in your README.

---

# Copyright

All trademarks remain the property of their respective owners.
