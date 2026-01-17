import { UserRepositoryInterface } from '../repositories/user.repository.interface.js';
import { PasswordService } from './password.service.js';
import { JwtService } from './jwt.service.js';
import { AuthenticationError } from '../../shared/errors/authentication.error.js';
import { ValidationError } from '../../shared/errors/validation.error.js';

export class AuthService {
    constructor(private userRepository: UserRepositoryInterface) { }

    async register(userData: any) {
        const existingUser = await this.userRepository.findByEmail(userData.email);
        if (existingUser) {
            throw new ValidationError({ email: ['Email already in use'] });
        }

        PasswordService.validate(userData.password);
        const passwordHash = await PasswordService.hash(userData.password);

        const user = await this.userRepository.create({
            ...userData,
            password_hash: passwordHash
        });

        const accessToken = JwtService.generateAccessToken(user);
        const refreshToken = JwtService.generateRefreshToken(user.id);

        // Save refresh token
        const expiresAt = new Date();
        expiresAt.setDate(expiresAt.getDate() + 7);
        await this.userRepository.saveRefreshToken(user.id, refreshToken, expiresAt);

        return {
            user: user.toJSON(),
            access_token: accessToken,
            refresh_token: refreshToken
        };
    }

    async login(email: string, password: string) {
        const result = await this.userRepository.findByEmailWithPassword(email);
        if (!result) {
            throw new AuthenticationError('Invalid email or password');
        }

        const { user, passwordHash } = result;
        const isPasswordValid = await PasswordService.verify(password, passwordHash);
        if (!isPasswordValid) {
            throw new AuthenticationError('Invalid email or password');
        }

        const accessToken = JwtService.generateAccessToken(user);
        const refreshToken = JwtService.generateRefreshToken(user.id);

        // Save refresh token
        const expiresAt = new Date();
        expiresAt.setDate(expiresAt.getDate() + 7);
        await this.userRepository.saveRefreshToken(user.id, refreshToken, expiresAt);

        return {
            user: user.toJSON(),
            access_token: accessToken,
            refresh_token: refreshToken
        };
    }

    async refresh(refreshToken: string) {
        try {
            const payload = JwtService.verifyRefreshToken(refreshToken);
            if (payload.type !== 'refresh') {
                throw new AuthenticationError('Invalid refresh token');
            }

            const storedToken = await this.userRepository.findRefreshToken(refreshToken);
            if (!storedToken || storedToken.expiresAt < new Date()) {
                throw new AuthenticationError('Refresh token expired or invalid');
            }

            const user = await this.userRepository.findById(storedToken.userId);
            if (!user) {
                throw new AuthenticationError('User not found');
            }

            const newAccessToken = JwtService.generateAccessToken(user);
            const newRefreshToken = JwtService.generateRefreshToken(user.id);

            // Rotate refresh tokens
            await this.userRepository.deleteRefreshToken(refreshToken);
            const expiresAt = new Date();
            expiresAt.setDate(expiresAt.getDate() + 7);
            await this.userRepository.saveRefreshToken(user.id, newRefreshToken, expiresAt);

            return {
                access_token: newAccessToken,
                refresh_token: newRefreshToken
            };
        } catch (error) {
            throw new AuthenticationError('Invalid refresh token');
        }
    }

    async logout(refreshToken: string) {
        await this.userRepository.deleteRefreshToken(refreshToken);
    }
}
