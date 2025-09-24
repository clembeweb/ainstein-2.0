import { prisma } from '../lib/db/prisma'
import { verifyPassword } from '../lib/auth/password'

async function testPassword() {
  try {
    const user = await prisma.user.findUnique({
      where: { email: 'admin@ainstein.com' }
    })

    if (!user) {
      console.log('User not found')
      return
    }

    console.log('Found user:', user.email)

    const passwords = [
      'Admin123!@#',
      'Admin123',
      'Admin123!',
      'admin123',
      'password'
    ]

    for (const password of passwords) {
      const isValid = await verifyPassword(password, user.passwordHash)
      console.log(`Password "${password}": ${isValid ? 'VALID' : 'Invalid'}`)
    }

  } catch (error) {
    console.error('Error:', error)
  } finally {
    await prisma.$disconnect()
  }
}

testPassword()