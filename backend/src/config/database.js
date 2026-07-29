import { Sequelize } from 'sequelize';
import dotenv from 'dotenv';

dotenv.config();

export const sequelize = new Sequelize({
  dialect: 'postgres',
  host: process.env.DATABASE_HOST || 'localhost',
  port: process.env.DATABASE_PORT || 5432,
  database: process.env.DATABASE_NAME || 'contributions_dev',
  username: process.env.DATABASE_USER || 'dev',
  password: process.env.DATABASE_PASSWORD || 'devpass',
  logging: process.env.LOG_LEVEL === 'debug' ? console.log : false,
  pool: {
    max: parseInt(process.env.DATABASE_POOL_MAX) || 10,
    min: parseInt(process.env.DATABASE_POOL_MIN) || 2,
    acquire: 30000,
    idle: 10000
  },
  dialectOptions: {
    ssl: process.env.NODE_ENV === 'production' ? { require: true, rejectUnauthorized: false } : false
  }
});

export default sequelize;
