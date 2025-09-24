import { prisma } from '../lib/db/prisma'

async function checkUsers() {
  try {
    const users = await prisma.user.findMany({
      include: { tenant: true }
    })

    console.log('Users in database:')
    users.forEach(user => {
      console.log('- Email:', user.email)
      console.log('  Name:', user.name)
      console.log('  Role:', user.role)
      console.log('  Super Admin:', user.isSuperAdmin)
      console.log('  Tenant:', user.tenant?.name || 'None')
      console.log('  Active:', user.isActive)
      console.log('---')
    })

    if (users.length === 0) {
      console.log('No users found!')
    }

  } catch (error) {
    console.error('Error:', error)
  } finally {
    await prisma.$disconnect()
  }
}

checkUsers()