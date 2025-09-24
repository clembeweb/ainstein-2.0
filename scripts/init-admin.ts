import { prisma } from '../lib/db/prisma'
import { hashPassword } from '../lib/auth/password'

async function initSuperAdmin() {
  const email = process.env.SUPER_ADMIN_EMAIL || 'admin@ainstein.com'
  const password = process.env.SUPER_ADMIN_PASSWORD || 'Admin123!@#'

  try {
    // Check if super admin exists
    const existingAdmin = await prisma.user.findUnique({
      where: { email }
    })

    if (existingAdmin) {
      console.log('Super Admin already exists')
      return
    }

    // Create super admin user
    const hashedPassword = await hashPassword(password)

    const superAdmin = await prisma.user.create({
      data: {
        email,
        passwordHash: hashedPassword,
        name: 'Super Admin',
        role: 'super_admin',
        isSuperAdmin: true,
        isActive: true,
        emailVerified: true
      }
    })

    console.log('Super Admin created successfully')
    console.log('Email:', email)
    console.log('Password:', password)
    console.log('ID:', superAdmin.id)

    // Create default platform settings
    const settings = await prisma.platformSettings.findFirst()
    if (!settings) {
      await prisma.platformSettings.create({
        data: {
          openaiModel: 'gpt-4o'
        }
      })
      console.log('Default platform settings created')
    }

  } catch (error) {
    console.error('Error creating super admin:', error)
  } finally {
    await prisma.$disconnect()
  }
}

initSuperAdmin()