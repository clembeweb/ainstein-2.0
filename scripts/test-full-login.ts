async function testFullLogin() {
  try {
    console.log('Testing full login process...')

    // Step 1: Login
    const loginResponse = await fetch('http://localhost:3000/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: 'admin@ainstein.com',
        password: 'Admin123!@#'
      })
    })

    console.log('Login response status:', loginResponse.status)

    if (!loginResponse.ok) {
      const error = await loginResponse.text()
      console.log('Login error:', error)
      return
    }

    const loginData = await loginResponse.json()
    console.log('Login successful! User:', loginData.user.email)
    console.log('Is Super Admin:', loginData.user.isSuperAdmin)

    // Extract cookie from response
    const setCookieHeader = loginResponse.headers.get('set-cookie')
    console.log('Set-Cookie header:', setCookieHeader)

    // Step 2: Test accessing protected route
    if (setCookieHeader) {
      const cookie = setCookieHeader.split(';')[0] // Get just the auth-token part
      console.log('Using cookie:', cookie)

      const dashboardResponse = await fetch('http://localhost:3000/admin/dashboard', {
        headers: {
          'Cookie': cookie
        }
      })

      console.log('Dashboard response status:', dashboardResponse.status)
      if (dashboardResponse.ok) {
        console.log('Dashboard access successful!')
      } else {
        console.log('Dashboard access failed')
      }
    }

  } catch (error) {
    console.error('Test error:', error)
  }
}

testFullLogin()