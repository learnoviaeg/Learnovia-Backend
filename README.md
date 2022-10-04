# How to Start?

#### 1- Setup development environment

- `cp .env.example .env`
- Install docker and docker-compose
- Build docker image by running `make build`
- Install dependencies `make install`
- Run the project `make run`
- Run migration `make migrate`

#### 2- Testing

- `cp .env.testing.example .env.testing`
- Run tests using `make test`, This will run all tests in the project.
